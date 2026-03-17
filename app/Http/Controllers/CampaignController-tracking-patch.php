<?php
/*
|----------------------------------------------------------------------
| PATCH: app/Http/Controllers/CampaignController.php
|
| In the dispatchSend() private method, inside the foreach loop,
| replace the existing send block with this:
|----------------------------------------------------------------------
*/

// Add at the top of CampaignController.php:
use App\Http\Controllers\TrackingController;

// Inside dispatchSend() foreach($contacts ...) loop, replace the try block:

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
