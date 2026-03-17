@extends('layouts.app')
@section('title', 'Email Providers')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Email Providers 📡</h1>
        <p class="page-subtitle">Configure and test your email sending providers</p>
    </div>
</div>

{{-- Provider Cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px;">
    @foreach($providers as $key => $p)
    <div class="card" style="position:relative;border-top:3px solid {{ $p['enabled'] ? $p['color'] : 'var(--border)' }};">
        {{-- Default badge --}}
        @if($p['default'])
        <div style="position:absolute;top:14px;right:14px;background:var(--gold-dim);color:var(--gold);font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:3px 10px;border-radius:100px;">
            DEFAULT
        </div>
        @endif

        <div style="padding:24px 24px 16px;">
            <div style="font-size:32px;margin-bottom:12px;">{{ $p['icon'] }}</div>
            <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;margin-bottom:6px;">{{ $p['label'] }}</h3>

            {{-- Status indicator --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $p['enabled'] ? 'var(--green)' : '#d1d5db' }};display:inline-block;"></span>
                <span style="font-size:13px;color:{{ $p['enabled'] ? 'var(--green)' : 'var(--muted)' }};font-weight:600;">
                    {{ $p['enabled'] ? 'Configured' : 'Not configured' }}
                </span>
            </div>

            @if(!$p['enabled'])
            <div style="background:#fef9e8;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;line-height:1.6;margin-bottom:16px;">
                ⚠️ Add credentials to <code style="background:#fef3c7;padding:1px 4px;border-radius:3px;">.env</code> to enable this provider.
            </div>
            @endif
        </div>

        {{-- Test buttons --}}
        @if($p['enabled'])
        <div style="border-top:1px solid var(--border);padding:14px 24px;display:flex;flex-direction:column;gap:8px;">
            <button onclick="pingProvider('{{ $key }}')"
                id="ping-{{ $key }}"
                class="btn btn-secondary btn-sm" style="justify-content:center;">
                🔌 Test Connection
            </button>
            <div id="ping-result-{{ $key }}" style="font-size:12px;min-height:18px;text-align:center;"></div>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- Send Test Email --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;align-items:start;">

    <div class="card">
        <div class="card-header">
            <div class="card-icon">📤</div>
            <div><h3>Send Test Email</h3><p>Verify full delivery through a provider</p></div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('providers.send-test') }}">
                @csrf
                <div class="form-group">
                    <label>Provider <span class="req">*</span></label>
                    <select name="provider" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;background:#fff;outline:none;">
                        @foreach($providers as $key => $p)
                            @if($p['enabled'])
                            <option value="{{ $key }}" {{ $key === $default ? 'selected' : '' }}>
                                {{ $p['icon'] }} {{ $p['label'] }} {{ $p['default'] ? '(default)' : '' }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Send to <span class="req">*</span></label>
                    <input type="email" name="to_email" value="{{ auth()->user()->email }}"
                        placeholder="test@example.com">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    📤 Send Test Email
                </button>
            </form>
        </div>
    </div>

    {{-- Fallback chain status --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔄</div>
            <div><h3>Fallback Chain</h3><p>Automatic failover order</p></div>
        </div>
        <div class="card-body">
            @php
                $allProviders  = config('providers.providers', []);
                $chain = array_filter(
                    array_merge([$default], $fallbackChain),
                    fn($k) => isset($allProviders[$k])
                );
            @endphp
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.6;">
                When a send fails, the platform automatically retries using the next configured provider in this order:
            </p>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach(array_values($chain) as $i => $key)
                @php $p = $allProviders[$key] ?? null; @endphp
                @if($p)
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--cream);border-radius:8px;border-left:3px solid {{ $i===0 ? $p['color'] : 'var(--border)' }};">
                    <span style="font-size:20px;">{{ $p['icon'] }}</span>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:14px;">{{ $p['label'] }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $i === 0 ? 'Primary' : 'Fallback #'.$i }}</div>
                    </div>
                    <span style="font-size:18px;color:{{ $p['enabled'] ? 'var(--green)' : '#d1d5db' }};">
                        {{ $p['enabled'] ? '✅' : '⚠️' }}
                    </span>
                </div>
                @endif
                @endforeach
            </div>
            @if(empty($fallbackChain))
            <p style="font-size:12px;color:var(--muted);margin-top:12px;">
                No fallback configured. Set <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">MAIL_PROVIDER_FALLBACK</code> in <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">.env</code>.
            </p>
            @endif
        </div>
    </div>

</div>

{{-- .env config guide --}}
<div class="card">
    <div class="card-header">
        <div class="card-icon">📋</div>
        <div><h3>.env Configuration</h3><p>Required environment variables for all three providers</p></div>
        <div class="card-header-action">
            <button onclick="copyEnv()" id="copyEnvBtn" class="btn btn-secondary btn-sm">📋 Copy</button>
        </div>
    </div>
    <div class="card-body">
        <pre id="envBlock" style="background:var(--ink);color:var(--gold);padding:20px 24px;border-radius:10px;font-size:12.5px;font-family:'Courier New',monospace;line-height:2;overflow-x:auto;white-space:pre;">{{ $envFile }}</pre>
    </div>
</div>

{{-- Per-provider setup notes --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:24px;">
    @foreach([
        ['⚡','Resend','resend.com/api-keys','Create an API key under API Keys. Make sure your sending domain is verified under Domains.',['re_xxxx format','Domain must be verified','Free tier: 3,000 emails/month']],
        ['☁️','Amazon SES','console.aws.amazon.com/ses','Create an IAM user with SES send permissions. Use us-east-1 or your nearest region. Request production access to remove sandbox limits.',['Requires AWS account','Sandbox: verified emails only','Production: request via console']],
        ['🪤','Mailtrap','mailtrap.io/api-tokens','Use the Email API (not SMTP). Get your token from API Tokens. Great for staging — emails are delivered to real inboxes.',['api.mailtrap.io endpoint','Separate from inbox sandbox','Free tier available']],
    ] as [$icon, $label, $url, $desc, $notes])
    <div class="card">
        <div class="card-header">
            <div class="card-icon">{{ $icon }}</div>
            <div><h3>{{ $label }}</h3></div>
        </div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);line-height:1.7;margin-bottom:14px;">{{ $desc }}</p>
            <a href="https://{{ $url }}" target="_blank" class="btn btn-secondary btn-sm" style="margin-bottom:14px;">
                🔗 Open {{ $label }} →
            </a>
            <ul style="font-size:12px;color:var(--muted);padding-left:16px;line-height:2;">
                @foreach($notes as $note)
                <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
async function pingProvider(key) {
    const btn    = document.getElementById('ping-' + key);
    const result = document.getElementById('ping-result-' + key);
    btn.disabled = true;
    btn.textContent = '⏳ Testing...';
    result.textContent = '';
    result.style.color = 'var(--muted)';

    try {
        const res  = await fetch('{{ route("providers.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ provider: key }),
        });
        const data = await res.json();

        if (data.success) {
            result.textContent = data.message || '✅ Connected!';
            result.style.color = 'var(--green)';
        } else {
            result.textContent = '❌ ' + (data.error || 'Failed');
            result.style.color = 'var(--red)';
        }
    } catch (e) {
        result.textContent = '❌ Network error';
        result.style.color = 'var(--red)';
    } finally {
        btn.disabled = false;
        btn.textContent = '🔌 Test Connection';
    }
}

function copyEnv() {
    const text = document.getElementById('envBlock').textContent;
    navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('copyEnvBtn');
        btn.textContent = '✅ Copied!';
        setTimeout(() => btn.textContent = '📋 Copy', 2000);
    });
}
</script>
@endpush
