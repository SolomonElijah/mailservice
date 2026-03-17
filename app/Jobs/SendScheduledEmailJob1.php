<?php

namespace App\Jobs;

use App\Http\Controllers\TrackingController;
use App\Mail\UserEmail;
use App\Models\EmailLog;
use App\Models\ScheduledEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendScheduledEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        public readonly int $scheduledEmailId
    ) {}

    public function handle(): void
    {
        $scheduled = ScheduledEmail::find($this->scheduledEmailId);

        if (!$scheduled || $scheduled->status === 'cancelled') {
            return;
        }

        $scheduled->update(['status' => 'processing']);

        try {
            $token = TrackingController::generateToken();
            $html  = TrackingController::injectTracking($scheduled->html_body, $token);

            Mail::to($scheduled->to_email, $scheduled->to_name)
                ->send(new UserEmail(
                    $scheduled->subject,
                    $html,
                    $scheduled->plain_body ?? '',
                    $scheduled->to_name ?? 'there'
                ));

            $scheduled->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

            EmailLog::create([
                'user_id'         => $scheduled->user_id,
                'recipient_email' => $scheduled->to_email,
                'recipient_name'  => $scheduled->to_name,
                'subject'         => $scheduled->subject,
                'type'            => 'single',
                'status'          => 'sent',
                'tracking_token'  => $token,
            ]);

        } catch (\Throwable $e) {
            $scheduled->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            EmailLog::create([
                'user_id'         => $scheduled->user_id,
                'recipient_email' => $scheduled->to_email,
                'recipient_name'  => $scheduled->to_name,
                'subject'         => $scheduled->subject,
                'type'            => 'single',
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
            ]);

            throw $e; // Allow retry
        }
    }
}
