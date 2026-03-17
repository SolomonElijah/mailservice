@extends('layouts.app')
@section('title', 'Send Email')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Send Email ✉️</h1>
        <p class="page-subtitle">Single recipient, bulk send, or advanced CC/BCC</p>
    </div>
    {{-- Active provider badge --}}
    @php
        $activeKey = config('providers.default', 'resend');
        $activeProv = config('providers.providers.' . $activeKey, []);
    @endphp
    <div style="display:flex;align-items:center;gap:10px;background:var(--cream);border:1.5px solid var(--border);border-radius:10px;padding:10px 16px;">
        <span style="font-size:20px;">{{ $activeProv['icon'] ?? '⚡' }}</span>
        <div>
            <div style="font-size:10px;color:var(--muted);font-weight:700;letter-spacing:.8px;text-transform:uppercase;">Active Provider</div>
            <div style="font-size:13px;font-weight:700;">{{ $activeProv['label'] ?? ucfirst($activeKey) }}</div>
        </div>
        <a href="{{ route('providers.index') }}" class="btn btn-secondary btn-sm">Change</a>
    </div>
</div>

{{-- Tabs --}}
<div style="display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:28px;">
    @foreach([
        ['single',   '✉️ Single Email'],
        ['multiple', '📬 Bulk Send'],
        ['advanced', '⚙️ Advanced (CC/BCC)'],
    ] as [$id, $label])
    <button class="email-tab {{ $id === 'single' ? 'etab-active' : '' }}"
        data-tab="{{ $id }}"
        onclick="switchEmailTab('{{ $id }}', this)"
        style="font-family:'Syne',sans-serif;font-weight:700;font-size:13px;padding:11px 22px;background:transparent;border:none;color:{{ $id === 'single' ? 'var(--ink)' : 'var(--muted)' }};cursor:pointer;border-bottom:2px solid {{ $id === 'single' ? 'var(--gold)' : 'transparent' }};margin-bottom:-2px;transition:all .2s;">
        {{ $label }}
    </button>
    @endforeach
</div>

{{-- ══ SINGLE EMAIL ══ --}}
<div id="panel-single" class="email-panel">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">✉️</div>
                <div><h3>Single Recipient</h3><p>Send a personalised email to one person</p></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.single') }}">
                    @csrf
                    @include('emails.partials.provider-select')
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>Recipient Email <span class="req">*</span></label>
                            <input type="email" name="to_email" value="{{ old('to_email') }}"
                                placeholder="recipient@example.com" inputmode="email"
                                class="{{ $errors->has('to_email') ? 'invalid' : '' }}">
                            @error('to_email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label>Recipient Name</label>
                            <input type="text" name="to_name" value="{{ old('to_name') }}" placeholder="John Doe">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject') }}"
                            placeholder="Your email subject line..."
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        @include('emails.partials.message-editor', ['form' => 'single', 'hasText' => true])
                        @error('html_body') <p class="field-error">{{ $message }}</p> @enderror
                        <p class="hint" style="margin-top:6px;">
                            Variables: <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{name}}</code>
                            <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{first_name}}</code>
                            <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{email}}</code>
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary">📤 Send Email</button>
                </form>
            </div>
        </div>
        @include('emails.partials.send-tips')
    </div>
</div>

{{-- ══ BULK SEND ══ --}}
<div id="panel-multiple" class="email-panel" style="display:none;">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">📬</div>
                <div><h3>Bulk Send</h3><p>Each recipient gets an individual email — not a group email</p></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.multiple') }}">
                    @csrf
                    @include('emails.partials.provider-select')
                    <div class="form-group">
                        <label>Recipients <span class="req">*</span></label>
                        <textarea name="emails" rows="5"
                            placeholder="user1@example.com, user2@example.com&#10;user3@example.com"
                            class="{{ $errors->has('emails') ? 'invalid' : '' }}">{{ old('emails') }}</textarea>
                        <p class="hint">Separate with commas, semicolons, or new lines.</p>
                        @error('emails') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject') }}"
                            placeholder="Your email subject..."
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        @include('emails.partials.message-editor', ['form' => 'multiple'])
                        @error('html_body') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">📤 Send to All</button>
                </form>
            </div>
        </div>
        @include('emails.partials.send-tips')
    </div>
</div>

{{-- ══ ADVANCED ══ --}}
<div id="panel-advanced" class="email-panel" style="display:none;">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">⚙️</div>
                <div><h3>Advanced — CC & BCC</h3><p>Full control over all recipients</p></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.advanced') }}">
                    @csrf
                    @include('emails.partials.provider-select')
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>To <span class="req">*</span></label>
                            <input type="email" name="to_email" value="{{ old('to_email') }}"
                                placeholder="primary@example.com"
                                class="{{ $errors->has('to_email') ? 'invalid' : '' }}">
                            @error('to_email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="to_name" value="{{ old('to_name') }}" placeholder="Recipient name">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>CC</label>
                            <input type="text" name="cc" value="{{ old('cc') }}" placeholder="cc@example.com">
                            <p class="hint">Separate multiple with commas</p>
                        </div>
                        <div class="form-group">
                            <label>BCC</label>
                            <input type="text" name="bcc" value="{{ old('bcc') }}" placeholder="bcc@example.com">
                            <p class="hint">Separate multiple with commas</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject') }}"
                            placeholder="Your email subject..."
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        @include('emails.partials.message-editor', ['form' => 'advanced'])
                        @error('html_body') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">📤 Send Email</button>
                </form>
            </div>
        </div>
        @include('emails.partials.send-tips')
    </div>
</div>

@endsection

@push('scripts')
<script>
function switchEmailTab(id, btn) {
    document.querySelectorAll('.email-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.email-tab').forEach(b => {
        b.style.color = 'var(--muted)';
        b.style.borderBottomColor = 'transparent';
        b.style.fontWeight = '700';
    });
    document.getElementById('panel-' + id).style.display = 'block';
    btn.style.color = 'var(--ink)';
    btn.style.borderBottomColor = 'var(--gold)';
    btn.style.fontWeight = '800';
}

function switchMsgTab(form, mode) {
    ['visual','html','text'].forEach(t => {
        const btn = document.getElementById(form + '-tab-' + t);
        const div = document.getElementById(form + '-editor-' + t);
        if (!btn || !div) return;
        const active = t === mode;
        btn.style.background = active ? 'var(--ink)' : 'var(--cream)';
        btn.style.color      = active ? '#fff'      : 'var(--muted)';
        div.style.display    = active ? 'block'     : 'none';
    });
    // Sync content when switching
    if (mode === 'visual') {
        const raw = document.getElementById(form + '-html-area');
        const vis = document.getElementById(form + '-editor-visual');
        if (raw && vis && raw.value) vis.innerHTML = raw.value;
    }
    if (mode === 'html') {
        const vis = document.getElementById(form + '-editor-visual');
        const raw = document.getElementById(form + '-html-area');
        if (vis && raw) raw.value = vis.innerHTML;
        syncHtmlToHidden(form);
    }
}

function syncVisualToHtml(form) {
    const vis    = document.getElementById(form + '-editor-visual');
    const hidden = document.getElementById(form + '-hidden-html');
    if (vis && hidden) hidden.value = vis.innerHTML;
}

function syncHtmlToHidden(form) {
    const raw    = document.getElementById(form + '-html-area');
    const hidden = document.getElementById(form + '-hidden-html');
    if (raw && hidden) hidden.value = raw.value;
}

function highlightProvider() {
    document.querySelectorAll('[id^="provider-label-"]').forEach(label => {
        const radio = label.querySelector('input[type=radio]');
        label.style.borderColor = radio && radio.checked ? 'var(--gold)' : 'var(--border)';
        label.style.background  = radio && radio.checked ? 'var(--gold-dim)' : '#fff';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    highlightProvider();
});
</script>
@endpush
