<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * POST /webhooks/resend
     * Receives events from Resend: delivered, opened, clicked, bounced,
     * complained, delivery_delayed.
     *
     * Set up in Resend dashboard:
     *   Endpoint: https://yourdomain.com/webhooks/resend
     *   Events:   email.delivered, email.opened, email.bounced,
     *             email.complained, email.clicked
     */
    public function resend(Request $request)
    {
        // ── Optional: verify Resend signature ──
        // Resend sends svix-id, svix-timestamp, svix-signature headers.
        // Uncomment below and set RESEND_WEBHOOK_SECRET in .env to verify.
        /*
        $secret    = config('services.resend.webhook_secret');
        $msgId     = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signature = $request->header('svix-signature');

        if (!$msgId || !$timestamp || !$signature) {
            return response()->json(['error' => 'Missing headers'], 401);
        }

        $toSign   = "{$msgId}.{$timestamp}." . $request->getContent();
        $computed = 'v1,' . base64_encode(hash_hmac('sha256', $toSign, base64_decode(ltrim($secret, 'whsec_')), true));

        $sigs = explode(' ', $signature);
        if (!in_array($computed, $sigs)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        */

        $payload   = $request->all();
        $eventType = $payload['type'] ?? 'unknown';
        $data      = $payload['data'] ?? [];
        $messageId = $data['email_id'] ?? null;
        $recipient = isset($data['to']) ? (is_array($data['to']) ? $data['to'][0] : $data['to']) : null;

        // Log raw event
        WebhookEvent::create([
            'event_type'        => $eventType,
            'resend_message_id' => $messageId,
            'recipient_email'   => $recipient,
            'payload'           => $payload,
            'processed'         => false,
        ]);

        // Find matching EmailLog by Resend message_id
        $log = $messageId ? EmailLog::where('message_id', $messageId)->first() : null;

        try {
            match ($eventType) {
                'email.delivered'        => $this->handleDelivered($log, $data),
                'email.opened'           => $this->handleOpened($log, $data),
                'email.clicked'          => $this->handleClicked($log, $data),
                'email.bounced'          => $this->handleBounced($log, $data, $recipient),
                'email.complained'       => $this->handleComplained($log, $data, $recipient),
                'email.delivery_delayed' => $this->handleDelayed($log, $data),
                default                  => null,
            };

            // Mark webhook processed
            WebhookEvent::where('resend_message_id', $messageId)
                ->where('event_type', $eventType)
                ->latest()
                ->update(['processed' => true]);

        } catch (\Throwable $e) {
            Log::error('Webhook processing error', [
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Event handlers ──────────────────────────

    private function handleDelivered(?EmailLog $log, array $data): void
    {
        if ($log && $log->status === 'sent') {
            $log->update(['status' => 'delivered']);
        }
    }

    private function handleOpened(?EmailLog $log, array $data): void
    {
        if ($log && !$log->opened_at) {
            $log->update([
                'status'    => 'opened',
                'opened_at' => now(),
            ]);
            $this->incrementCampaignStat($log, 'opened_count');
        }
    }

    private function handleClicked(?EmailLog $log, array $data): void
    {
        if ($log && !$log->clicked_at) {
            $log->update([
                'status'     => 'clicked',
                'clicked_at' => now(),
            ]);
            $this->incrementCampaignStat($log, 'clicked_count');
        }
    }

    private function handleBounced(?EmailLog $log, array $data, ?string $email): void
    {
        if ($log) {
            $log->update([
                'status'        => 'bounced',
                'error_message' => $data['bounce']['message'] ?? 'Bounced',
            ]);
            $this->incrementCampaignStat($log, 'failed_count');
        }

        // Mark contact as bounced in all lists
        if ($email) {
            Contact::where('email', $email)->update(['status' => 'bounced']);
        }
    }

    private function handleComplained(?EmailLog $log, array $data, ?string $email): void
    {
        if ($log) {
            $log->update(['status' => 'failed', 'error_message' => 'Spam complaint']);
        }

        // Auto-unsubscribe on complaint
        if ($email) {
            Contact::where('email', $email)->update(['status' => 'unsubscribed']);
        }
    }

    private function handleDelayed(?EmailLog $log, array $data): void
    {
        // Just log — no status change needed
    }

    // ── Helpers ────────────────────────────────

    private function incrementCampaignStat(?EmailLog $log, string $column): void
    {
        if ($log && $log->campaign_name && $log->user_id) {
            Campaign::where('user_id', $log->user_id)
                ->where('name', $log->campaign_name)
                ->increment($column);
        }
    }
}
