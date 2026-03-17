<?php

namespace App\Jobs;

use App\Http\Controllers\TrackingController;
use App\Models\EmailLog;
use App\Models\ScheduledEmail;
use App\Services\MailProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    public function __construct(public readonly int $scheduledEmailId) {}

    public function handle(MailProviderService $mailer): void
    {
        $scheduled = ScheduledEmail::find($this->scheduledEmailId);

        if (!$scheduled || $scheduled->status === 'cancelled') {
            return;
        }

        $scheduled->update(['status' => 'processing']);

        $token = TrackingController::generateToken();
        $html  = TrackingController::injectTracking($scheduled->html_body, $token);

        $result = $mailer->send([
            'to_email' => $scheduled->to_email,
            'to_name'  => $scheduled->to_name ?? '',
            'subject'  => $scheduled->subject,
            'html'     => $html,
            'text'     => $scheduled->plain_body ?? '',
        ], $scheduled->provider ?: null);

        $scheduled->update([
            'status'        => $result['success'] ? 'sent' : 'failed',
            'sent_at'       => $result['success'] ? now() : null,
            'error_message' => $result['error'],
        ]);

        EmailLog::create([
            'user_id'         => $scheduled->user_id,
            'recipient_email' => $scheduled->to_email,
            'recipient_name'  => $scheduled->to_name,
            'subject'         => $scheduled->subject,
            'type'            => 'single',
            'status'          => $result['success'] ? 'sent' : 'failed',
            'provider'        => $result['provider'],
            'tracking_token'  => $token,
            'error_message'   => $result['error'],
        ]);

        if (!$result['success']) {
            throw new \RuntimeException($result['error'] ?? 'Send failed');
        }
    }
}
