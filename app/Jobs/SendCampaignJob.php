<?php

namespace App\Jobs;

use App\Http\Controllers\TrackingController;
use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailLog;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a campaign email to a SINGLE contact.
 * Dispatched in bulk by CampaignController — one job per contact.
 */
class SendCampaignJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly int $campaignId,
        public readonly int $contactId,
    ) {}

    public function handle(): void
    {
        // Bail if batch was cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        $campaign = Campaign::find($this->campaignId);
        $contact  = Contact::find($this->contactId);

        if (!$campaign || !$contact) {
            return;
        }

        try {
            $token   = TrackingController::generateToken();
            $html    = $this->personalise($campaign->html_content, $contact);
            $tracked = TrackingController::injectTracking($html, $token, $campaign->id);

            $mailable = new CampaignEmail(
                $campaign->subject,
                $tracked,
                $campaign->plain_content ?? '',
                $contact->name ?? 'Subscriber'
            );

            if ($campaign->from_email) {
                $mailable->from($campaign->from_email, $campaign->from_name);
            }

            Mail::to($contact->email, $contact->name)->send($mailable);

            $campaign->increment('sent_count');

            EmailLog::create([
                'user_id'         => $campaign->user_id,
                'recipient_email' => $contact->email,
                'recipient_name'  => $contact->name,
                'subject'         => $campaign->subject,
                'type'            => 'campaign',
                'status'          => 'sent',
                'campaign_name'   => $campaign->name,
                'tracking_token'  => $token,
            ]);

        } catch (\Throwable $e) {
            $campaign->increment('failed_count');

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
    }

    private function personalise(string $html, Contact $contact): string
    {
        $name      = $contact->name ?? 'Subscriber';
        $firstName = explode(' ', $name)[0];
        return str_replace(
            ['{{name}}', '{{first_name}}', '{{email}}', '{{company}}'],
            [$name, $firstName, $contact->email, $contact->company ?? ''],
            $html
        );
    }
}
