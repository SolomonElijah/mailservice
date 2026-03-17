<?php

namespace App\Http\Controllers;

use App\Http\Controllers\TrackingController;
use App\Models\EmailLog;
use App\Services\MailProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    public function __construct(private MailProviderService $mailer) {}

    public function index()
    {
        $providers = array_filter(
            config('providers.providers', []),
            fn($p) => $p['enabled'] ?? false
        );
        $defaultProvider = config('providers.default', 'resend');

        return view('emails.compose', compact('providers', 'defaultProvider'));
    }

    // ── Single Email ──────────────────────────────────────────

    public function sendSingle(Request $request)
    {
        $validated = $request->validate([
            'to_email'   => 'required|email',
            'to_name'    => 'nullable|string|max:100',
            'subject'    => 'required|string|max:255',
            'html_body'  => 'required|string',
            'plain_body' => 'nullable|string',
            'provider'   => 'nullable|string',
        ]);

        $token = TrackingController::generateToken();
        $html  = TrackingController::injectTracking($validated['html_body'], $token);

        $result = $this->mailer->send([
            'to_email' => $validated['to_email'],
            'to_name'  => $validated['to_name'] ?? '',
            'subject'  => $validated['subject'],
            'html'     => $html,
            'text'     => $validated['plain_body'] ?? '',
        ], $request->input('provider') ?: null);

        EmailLog::create([
            'user_id'         => Auth::id(),
            'recipient_email' => $validated['to_email'],
            'recipient_name'  => $validated['to_name'] ?? null,
            'subject'         => $validated['subject'],
            'type'            => 'single',
            'status'          => $result['success'] ? 'sent' : 'failed',
            'provider'        => $result['provider'],
            'tracking_token'  => $token,
            'error_message'   => $result['error'],
        ]);

        if ($result['success']) {
            return back()->with('success', "✅ Email sent via " . strtoupper($result['provider']) . "!");
        }

        return back()->with('error', "❌ Failed via " . strtoupper($result['provider']) . ": " . $result['error']);
    }

    // ── Multiple Emails ───────────────────────────────────────

    public function sendMultiple(Request $request)
    {
        $validated = $request->validate([
            'emails'     => 'required|string',
            'subject'    => 'required|string|max:255',
            'html_body'  => 'required|string',
            'plain_body' => 'nullable|string',
            'provider'   => 'nullable|string',
        ]);

        $provider    = $request->input('provider') ?: null;
        $rawEmails   = preg_split('/[\s,;]+/', trim($validated['emails']));
        $validEmails = array_filter($rawEmails, fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL));

        if (empty($validEmails)) {
            return back()->with('error', 'No valid email addresses found.')->withInput();
        }

        $sent = $failed = 0;
        $lastProvider = config('providers.default');

        foreach (array_values($validEmails) as $index => $email) {
            // Rate limit: 2 per second
            if ($index > 0 && $index % 2 === 0) {
                usleep(1100000);
            }

            $token = TrackingController::generateToken();
            $html  = TrackingController::injectTracking($validated['html_body'], $token);

            $result = $this->mailer->send([
                'to_email' => trim($email),
                'subject'  => $validated['subject'],
                'html'     => $html,
                'text'     => $validated['plain_body'] ?? '',
            ], $provider);

            $lastProvider = $result['provider'];

            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => trim($email),
                'subject'         => $validated['subject'],
                'type'            => 'multiple',
                'status'          => $result['success'] ? 'sent' : 'failed',
                'provider'        => $result['provider'],
                'tracking_token'  => $token,
                'error_message'   => $result['error'],
            ]);

            $result['success'] ? $sent++ : $failed++;
        }

        $msg = "{$sent} email(s) sent via " . strtoupper($lastProvider) . ".";
        if ($failed) $msg .= " {$failed} failed.";

        return back()->with($failed && !$sent ? 'error' : 'success', $msg);
    }

    // ── Advanced (per-recipient personalisation) ──────────────

    public function sendAdvanced(Request $request)
    {
        $request->validate([
            'recipients' => 'required|string',
            'subject'    => 'required|string|max:255',
            'html_body'  => 'required|string',
            'provider'   => 'nullable|string',
        ]);

        $provider = $request->input('provider') ?: null;
        $lines    = preg_split('/\r?\n/', trim($request->recipients));
        $sent = $failed = 0;
        $lastProvider = config('providers.default');

        foreach (array_values(array_filter($lines)) as $index => $line) {
            if ($index > 0 && $index % 2 === 0) usleep(1100000);

            $parts = array_map('trim', explode(',', $line, 3));
            $email = $parts[0] ?? '';
            $name  = $parts[1] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $token   = TrackingController::generateToken();
            $subject = str_replace(['{{name}}', '{{first_name}}'], [$name, explode(' ', $name)[0]], $request->subject);
            $html    = str_replace(['{{name}}', '{{first_name}}', '{{email}}'], [$name, explode(' ', $name)[0], $email], $request->html_body);
            $html    = TrackingController::injectTracking($html, $token);

            $result = $this->mailer->send([
                'to_email' => $email,
                'to_name'  => $name,
                'subject'  => $subject,
                'html'     => $html,
                'text'     => strip_tags($html),
            ], $provider);

            $lastProvider = $result['provider'];

            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => $email,
                'recipient_name'  => $name,
                'subject'         => $subject,
                'type'            => 'advanced',
                'status'          => $result['success'] ? 'sent' : 'failed',
                'provider'        => $result['provider'],
                'tracking_token'  => $token,
                'error_message'   => $result['error'],
            ]);

            $result['success'] ? $sent++ : $failed++;
        }

        $msg = "{$sent} email(s) sent via " . strtoupper($lastProvider) . ".";
        if ($failed) $msg .= " {$failed} failed.";
        return back()->with($failed && !$sent ? 'error' : 'success', $msg);
    }
}
