<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\ContactList;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\MailProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function __construct(private MailProviderService $mailer) {}

    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())->latest()->paginate(15);
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
        $providers    = array_filter(config('providers.providers', []), fn($p) => $p['enabled'] ?? false);
        $defaultProvider = config('providers.default', 'resend');
        return view('campaigns.create', compact('templates', 'contactLists', 'types', 'providers', 'defaultProvider'));
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
            'provider'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $status = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        $campaign = Campaign::create([
            'user_id'           => Auth::id(),
            'email_template_id' => $request->email_template_id ?: null,
            'contact_list_id'   => $request->contact_list_id ?: null,
            'name'              => $request->name,
            'subject'           => $request->subject,
            'html_content'      => $request->html_content,
            'plain_content'     => $request->plain_content,
            'from_name'         => $request->from_name,
            'from_email'        => $request->from_email,
            'type'              => $request->type,
            'status'            => $status,
            'scheduled_at'      => $request->scheduled_at,
            'provider'          => $request->provider ?: null,
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
        $templates       = EmailTemplate::where('user_id', Auth::id())->get();
        $contactLists    = ContactList::where('user_id', Auth::id())->withCount('contacts')->get();
        $types           = Campaign::types();
        $providers       = array_filter(config('providers.providers', []), fn($p) => $p['enabled'] ?? false);
        $defaultProvider = config('providers.default', 'resend');
        return view('campaigns.edit', compact('campaign', 'templates', 'contactLists', 'types', 'providers', 'defaultProvider'));
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
            'provider'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $campaign->update($request->only(
            'name', 'subject', 'type', 'html_content', 'plain_content',
            'from_name', 'from_email', 'contact_list_id', 'scheduled_at', 'provider'
        ));

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully!');
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
        $new->name        = $campaign->name . ' (Copy)';
        $new->status      = 'draft';
        $new->sent_at     = null;
        $new->sent_count  = 0;
        $new->failed_count = 0;
        $new->save();
        return redirect()->route('campaigns.edit', $new)
            ->with('success', 'Campaign duplicated.');
    }

    // ── Core send logic ───────────────────────────────────────

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

        $sent = $failed = 0;
        $provider = $campaign->provider ?: null;

        foreach ($contacts as $index => $contact) {
            if ($index > 0 && $index % 2 === 0) {
                usleep(1100000);
            }

            $token   = TrackingController::generateToken();
            $html    = $this->personalise($campaign->html_content, $contact);
            $tracked = TrackingController::injectTracking($html, $token, $campaign->id);

            $result = $this->mailer->send([
                'to_email'   => $contact->email,
                'to_name'    => $contact->name ?? '',
                'subject'    => $campaign->subject,
                'html'       => $tracked,
                'text'       => $campaign->plain_content ?? '',
                'from_email' => $campaign->from_email,
                'from_name'  => $campaign->from_name,
            ], $provider);

            EmailLog::create([
                'user_id'         => $campaign->user_id,
                'recipient_email' => $contact->email,
                'recipient_name'  => $contact->name,
                'subject'         => $campaign->subject,
                'type'            => 'campaign',
                'status'          => $result['success'] ? 'sent' : 'failed',
                'provider'        => $result['provider'],
                'campaign_name'   => $campaign->name,
                'tracking_token'  => $token,
                'error_message'   => $result['error'],
            ]);

            $result['success'] ? $sent++ : $failed++;
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

    private function personalise(string $html, $contact): string
    {
        $name      = $contact->name ?? 'Subscriber';
        $firstName = explode(' ', $name)[0];
        return str_replace(
            ['{{name}}', '{{first_name}}', '{{email}}', '{{company}}'],
            [$name, $firstName, $contact->email, $contact->company ?? ''],
            $html
        );
    }

    private function authorize(Campaign $campaign): void
    {
        abort_if($campaign->user_id !== Auth::id(), 403);
    }
}
