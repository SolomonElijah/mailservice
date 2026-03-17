@extends('layouts.app')
@section('title', 'Webhook Setup')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Webhook Setup 🔗</h1>
        <p class="page-subtitle">Configure Resend to send delivery events to your app</p>
    </div>
    <a href="{{ route('analytics.index') }}" class="btn btn-secondary">← Back to Analytics</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Step 1 --}}
        <div class="card">
            <div class="card-header">
                <div style="width:32px;height:32px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--gold);flex-shrink:0;">1</div>
                <div><h3>Go to Resend Webhooks</h3><p>Open your Resend dashboard</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:16px;line-height:1.7;">
                    Log in to <strong>resend.com</strong>, go to <strong>Webhooks</strong> in the left sidebar, then click <strong>Add Webhook</strong>.
                </p>
                <a href="https://resend.com/webhooks" target="_blank" class="btn btn-primary">Open Resend Webhooks →</a>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="card">
            <div class="card-header">
                <div style="width:32px;height:32px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--gold);flex-shrink:0;">2</div>
                <div><h3>Set the Endpoint URL</h3><p>Paste this URL into Resend</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:12px;">Copy this URL and paste it as the webhook endpoint in Resend:</p>
                <div style="display:flex;gap:8px;align-items:center;">
                    <code id="webhookUrl" style="flex:1;background:var(--ink);color:var(--gold);padding:12px 16px;border-radius:8px;font-size:13px;font-family:'Courier New',monospace;word-break:break-all;">
                        {{ config('app.url') }}/webhooks/resend
                    </code>
                    <button onclick="copyUrl()" class="btn btn-secondary btn-sm" style="flex-shrink:0;" id="copyBtn">📋 Copy</button>
                </div>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="card">
            <div class="card-header">
                <div style="width:32px;height:32px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--gold);flex-shrink:0;">3</div>
                <div><h3>Select Events to Track</h3><p>Choose which events to subscribe to</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:16px;">Enable these events in Resend:</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach([
                        ['email.delivered',        '🟢', 'Confirms delivery to inbox'],
                        ['email.opened',           '👁',  'Recipient opened the email'],
                        ['email.clicked',          '🖱️', 'Recipient clicked a link'],
                        ['email.bounced',          '⛔', 'Email couldn\'t be delivered'],
                        ['email.complained',       '🚨', 'Marked as spam'],
                        ['email.delivery_delayed', '⏳', 'Temporary delivery delay'],
                    ] as [$event, $icon, $desc])
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--cream);border-radius:8px;">
                        <span style="font-size:16px;">{{ $icon }}</span>
                        <div>
                            <code style="font-size:12px;color:var(--ink);font-family:'Courier New',monospace;font-weight:700;">{{ $event }}</code>
                            <div style="font-size:12px;color:var(--muted);">{{ $desc }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Step 4 (optional signature verification) --}}
        <div class="card">
            <div class="card-header">
                <div style="width:32px;height:32px;background:var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--gold);flex-shrink:0;">4</div>
                <div><h3>Optional: Verify Signatures</h3><p>Recommended for production</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:14px;line-height:1.7;">
                    Resend signs every webhook with a secret. To verify authenticity, add this to your <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">.env</code>:
                </p>
                <code style="display:block;background:var(--ink);color:var(--gold);padding:12px 16px;border-radius:8px;font-size:13px;font-family:'Courier New',monospace;">RESEND_WEBHOOK_SECRET=whsec_xxxxx</code>
                <p style="font-size:13px;color:var(--muted);margin-top:10px;line-height:1.5;">
                    Then uncomment the signature verification block in <code style="background:#f0ede8;padding:1px 4px;border-radius:3px;font-size:11px;">WebhookController.php</code>.
                </p>
            </div>
        </div>

    </div>

    {{-- Right: status + test --}}
    <div style="position:sticky;top:72px;display:flex;flex-direction:column;gap:16px;">

        {{-- Live status --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">📊</div>
                <div><h3>Webhook Status</h3><p>Events received so far</p></div>
            </div>
            <div class="card-body" style="padding:0;">
                @php
                    $counts = \App\Models\WebhookEvent::selectRaw('event_type, COUNT(*) as cnt')
                        ->groupBy('event_type')->pluck('cnt','event_type');
                @endphp
                @php $total = $counts->sum(); @endphp
                @if($total > 0)
                <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);">Total events received</span>
                    <strong style="font-family:'Syne',sans-serif;">{{ number_format($total) }}</strong>
                </div>
                @foreach([
                    'email.delivered','email.opened','email.clicked',
                    'email.bounced','email.complained','email.delivery_delayed'
                ] as $ev)
                <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);">{{ str_replace('email.','',$ev) }}</span>
                    <strong>{{ $counts[$ev] ?? 0 }}</strong>
                </div>
                @endforeach
                @else
                <div style="text-align:center;padding:32px 20px;color:var(--muted);">
                    <div style="font-size:32px;margin-bottom:8px;">📭</div>
                    <p style="font-size:13px;">No events received yet.<br>Complete setup and send a test email.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Tracking status --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🎯</div>
                <div><h3>Pixel Tracking</h3><p>Open + click tracking status</p></div>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                @php
                    $hasToken   = \App\Models\EmailLog::whereNotNull('tracking_token')->exists();
                    $totalOpens = \App\Models\EmailLog::whereNotNull('opened_at')->count();
                    $totalClicks= \App\Models\EmailClick::count();
                @endphp
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $hasToken ? 'var(--green)' : 'var(--red)' }};display:inline-block;"></span>
                    <span style="font-size:13px;font-weight:600;">Pixel tracking {{ $hasToken ? 'active' : 'not yet seen' }}</span>
                </div>
                @foreach([
                    ['Total opens tracked',  $totalOpens,  'var(--gold)'],
                    ['Total clicks tracked', $totalClicks, 'var(--green)'],
                ] as [$label, $val, $color])
                <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px;">
                    <span style="color:var(--muted);">{{ $label }}</span>
                    <strong style="color:{{ $color }};font-family:'Syne',sans-serif;">{{ number_format($val) }}</strong>
                </div>
                @endforeach
                <p style="font-size:12px;color:var(--muted);margin-top:12px;line-height:1.6;">
                    Tracking pixels are injected automatically into every email sent through Campaigns and Send Email pages.
                </p>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function copyUrl() {
    const url = document.getElementById('webhookUrl').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.textContent = '✅ Copied!';
        setTimeout(() => btn.textContent = '📋 Copy', 2000);
    });
}
</script>
@endpush
