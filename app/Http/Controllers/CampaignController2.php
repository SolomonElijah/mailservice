<?php

namespace App\Http\Controllers;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\ContactList;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\TrackingController;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        $stats = [
            'total'     => Campaign::where('user_id', Auth::id())->count(),
            'sent'      => Campaign::where('user_id', Auth::id())->where('status', 'sent')->count(),
            'draft'     => Campaign::where('user_id', Auth::id())->where('status', 'draft')->count(),
            'scheduled' => Campaign::where('user_id', Auth::id())->where('status', 'scheduled')->count(),
        ];

        return view('campaigns.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        $templates    = EmailTemplate::where('user_id', Auth::id())->get();
        $contactLists = ContactList::where('user_id', Auth::id())->withCount('contacts')->get();
        $types        = Campaign::types();
        return view('campaigns.create', compact('templates', 'contactLists', 'types'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:150',
            'subject'         => 'required|string|max:255',
            'type'            => 'required|string',
            'html_content'    => 'required|string',
            'from_name'       => 'nullable|string|max:100',
            'from_email'      => 'nullable|email',
            'contact_list_id' => 'nullable|exists:contact_lists,id',
            'scheduled_at'    => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $status = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        $campaign = Campaign::create([
            'user_id'         => Auth::id(),
            'email_template_id' => $request->email_template_id ?: null,
            'contact_list_id' => $request->contact_list_id ?: null,
            'name'            => $request->name,
            'subject'         => $request->subject,
            'html_content'    => $request->html_content,
            'plain_content'   => $request->plain_content,
            'from_name'       => $request->from_name,
            'from_email'      => $request->from_email,
            'type'            => $request->type,
            'status'          => $status,
            'scheduled_at'    => $request->scheduled_at,
        ]);

        if ($request->has('send_now')) {
            return $this->dispatchSend($campaign);
        }

        return redirect()->route('campaigns.index')
            ->with('success', "Campaign \"{$campaign->name}\" saved as {$status}!");
    }

    public function show(Campaign $campaign)
    {
        $this->authorize($campaign);
        return view('campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        $this->authorize($campaign);
        abort_if(in_array($campaign->status, ['sending', 'sent']), 403, 'Cannot edit a sent campaign.');
        $templates    = EmailTemplate::where('user_id', Auth::id())->get();
        $contactLists = ContactList::where('user_id', Auth::id())->withCount('contacts')->get();
        $types        = Campaign::types();
        return view('campaigns.edit', compact('campaign', 'templates', 'contactLists', 'types'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->authorize($campaign);
        abort_if(in_array($campaign->status, ['sending', 'sent']), 403);

        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:150',
            'subject'         => 'required|string|max:255',
            'type'            => 'required|string',
            'html_content'    => 'required|string',
            'from_name'       => 'nullable|string|max:100',
            'from_email'      => 'nullable|email',
            'contact_list_id' => 'nullable|exists:contact_lists,id',
            'scheduled_at'    => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $campaign->update($request->only(
            'name','subject','type','html_content','plain_content',
            'from_name','from_email','contact_list_id','scheduled_at'
        ));

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully!');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize($campaign);
        $campaign->delete();
        return back()->with('success', 'Campaign deleted.');
    }

    public function send(Campaign $campaign)
    {
        $this->authorize($campaign);
        abort_if($campaign->status === 'sent', 403, 'Campaign already sent.');
        return $this->dispatchSend($campaign);
    }

    public function duplicate(Campaign $campaign)
    {
        $this->authorize($campaign);
        $new = $campaign->replicate();
        $new->name   = $campaign->name . ' (Copy)';
        $new->status = 'draft';
        $new->sent_at = null;
        $new->sent_count = 0;
        $new->failed_count = 0;
        $new->save();
        return redirect()->route('campaigns.edit', $new)
            ->with('success', 'Campaign duplicated. Edit and send when ready.');
    }

    // ── Core send logic ──
    private function dispatchSend(Campaign $campaign)
    {
        if (!$campaign->contact_list_id) {
            return redirect()->route('campaigns.edit', $campaign)
                ->with('error', 'Please select a contact list before sending.');
        }

        $contacts = $campaign->contactList->activeContacts()->get();

        if ($contacts->isEmpty()) {
            return redirect()->route('campaigns.index')
                ->with('error', 'The selected contact list has no active subscribers.');
        }

        $campaign->update([
            'status'           => 'sending',
            'total_recipients' => $contacts->count(),
        ]);

        $sent   = 0;
        $failed = 0;

        foreach ($contacts as $index => $contact) {
        
    try {
        if ($index > 0 && $index % 2 === 0) {
            usleep(1100000);
        }

        // Generate unique tracking token per recipient
        $token = TrackingController::generateToken();

        // Personalise + inject tracking
        $html    = $this->personalise($campaign->html_content, $contact);
        $tracked = TrackingController::injectTracking($html, $token, $campaign->id);

        $mailable = new CampaignEmail(
            $campaign->subject,
            $tracked,                          // ← tracked HTML
            $campaign->plain_content ?? '',
            $contact->name ?? 'Subscriber'
        );

        $mailer = Mail::to($contact->email, $contact->name);

        if ($campaign->from_email) {
            $mailable->from($campaign->from_email, $campaign->from_name);
        }

        $mailer->send($mailable);
        $sent++;

        EmailLog::create([
            'user_id'         => $campaign->user_id,
            'recipient_email' => $contact->email,
            'recipient_name'  => $contact->name,
            'subject'         => $campaign->subject,
            'type'            => 'campaign',
            'status'          => 'sent',
            'campaign_name'   => $campaign->name,
            'tracking_token'  => $token,         // ← NEW
        ]);

    } catch (\Exception $e) {
        $failed++;
        EmailLog::create([
            'user_id'         => $campaign->user_id,
            'recipient_email' => $contact->email,
            'recipient_name'  => $contact->name,
            'subject'         => $campaign->subject,
            'type'            => 'campaign',
            'status'          => 'failed',
            'campaign_name'   => $campaign->name,
            'error_message'   => $e->getMessage(),
        ]);
        }

        $campaign->update([
            'status'       => 'sent',
            'sent_at'      => now(),
            'sent_count'   => $sent,
            'failed_count' => $failed,
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "Campaign sent! {$sent} delivered, {$failed} failed.");
    }
    }

    private function personalise(string $html, $contact): string
    {
        $name       = $contact->name ?? 'Subscriber';
        $firstName  = explode(' ', $name)[0];
        $replacements = [
            '{{name}}'       => $name,
            '{{first_name}}' => $firstName,
            '{{email}}'      => $contact->email,
            '{{company}}'    => $contact->company ?? '',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    private function authorize(Campaign $campaign): void
    {
        abort_if($campaign->user_id !== Auth::id(), 403);
    }
}
