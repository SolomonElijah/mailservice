<?php

namespace App\Http\Controllers;

use App\Services\MailProviderService;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(private MailProviderService $mailer) {}

    /**
     * GET /providers
     * Show provider status page.
     */
    public function index()
    {
        $providers       = $this->mailer->status();
        $default         = config('providers.default');
        $fallbackChain   = config('providers.fallback', []);
        $envFile         = $this->buildEnvExample();

        return view('providers.index', compact('providers', 'default', 'fallbackChain', 'envFile'));
    }

    /**
     * POST /providers/test
     * Ping a provider to verify its credentials.
     */
    public function test(Request $request)
    {
        $provider = $request->input('provider');

        if (!in_array($provider, ['resend', 'ses', 'mailtrap'])) {
            return response()->json(['success' => false, 'error' => 'Unknown provider.'], 422);
        }

        $result = $this->mailer->test($provider);

        return response()->json($result);
    }

    /**
     * POST /providers/send-test
     * Send an actual test email through a chosen provider to verify full flow.
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:resend,ses,mailtrap',
            'to_email' => 'required|email',
        ]);

        $result = $this->mailer->send([
            'to_email' => $request->to_email,
            'to_name'  => 'Test Recipient',
            'subject'  => '✅ Test email from ' . config('app.name'),
            'html'     => $this->testEmailHtml($request->provider),
            'text'     => 'This is a test email sent from ' . config('app.name') . ' via ' . strtoupper($request->provider) . '.',
        ], $request->provider);

        if ($result['success']) {
            return back()->with('success',
                "✅ Test email sent via " . strtoupper($result['provider']) . "! Check {$request->to_email}."
            );
        }

        return back()->with('error',
            "❌ Failed via " . strtoupper($result['provider']) . ": " . $result['error']
        );
    }

    // ─────────────────────────────────────────
    private function buildEnvExample(): string
    {
        return <<<ENV
# ── Email Provider Config ─────────────────────────────
# Default provider: resend | ses | mailtrap
MAIL_PROVIDER=resend

# Fallback chain (tried in order on failure, comma-separated)
MAIL_PROVIDER_FALLBACK=ses,mailtrap

# ── Resend ────────────────────────────────────────────
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxxxxxxxxx

# ── Amazon SES (HTTP API) ─────────────────────────────
AWS_ACCESS_KEY_ID=AKIAxxxxxxxxxxxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
AWS_DEFAULT_REGION=us-east-1
AWS_SES_FROM_EMAIL=noreply@yourdomain.com

# ── Mailtrap (API sending) ────────────────────────────
MAILTRAP_API_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAILTRAP_FROM_EMAIL=noreply@yourdomain.com
ENV;
    }

    private function testEmailHtml(string $provider): string
    {
        $labels = ['resend' => '⚡ Resend', 'ses' => '☁️ Amazon SES', 'mailtrap' => '🪤 Mailtrap'];
        $label  = $labels[$provider] ?? $provider;
        $app    = config('app.name');
        $time   = now()->format('M j, Y g:i A');

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f5f5f5;padding:32px;color:#222}
.box{background:#fff;border-radius:10px;padding:32px;max-width:480px;margin:0 auto;box-shadow:0 2px 16px rgba(0,0,0,.08)}
h2{color:#0d0d14;margin-bottom:8px}
.badge{display:inline-block;background:#d4a84320;color:#d4a843;font-weight:700;padding:4px 14px;border-radius:100px;font-size:14px;margin-bottom:20px}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:14px}
.label{color:#888}.val{font-weight:600}
</style></head><body>
<div class="box">
  <h2>✅ Test Email Successful</h2>
  <div class="badge">{$label}</div>
  <div class="row"><span class="label">App</span><span class="val">{$app}</span></div>
  <div class="row"><span class="label">Provider</span><span class="val">{$label}</span></div>
  <div class="row"><span class="label">Sent at</span><span class="val">{$time}</span></div>
  <p style="font-size:13px;color:#aaa;margin-top:20px;">If you received this, your provider is correctly configured.</p>
</div></body></html>
HTML;
    }
}
