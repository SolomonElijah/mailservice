{{-- Reusable provider dropdown for all send forms --}}
@php
    $enabledProviders = array_filter(config('providers.providers', []), fn($p) => $p['enabled'] ?? false);
    $defaultProvider  = config('providers.default', 'resend');
@endphp
@if(count($enabledProviders) > 1)
<div class="form-group">
    <label>Send Via</label>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($enabledProviders as $key => $p)
        <label style="display:flex;align-items:center;gap:8px;padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:border-color .15s;"
            id="provider-label-{{ $key }}">
            <input type="radio" name="provider" value="{{ $key }}"
                style="accent-color:var(--gold);"
                {{ $defaultProvider === $key ? 'checked' : '' }}
                onchange="highlightProvider()">
            <span>{{ $p['icon'] }}</span> {{ $p['label'] }}
        </label>
        @endforeach
        <label style="display:flex;align-items:center;gap:8px;padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:border-color .15s;"
            id="provider-label-auto">
            <input type="radio" name="provider" value=""
                style="accent-color:var(--gold);"
                onchange="highlightProvider()">
            <span>🔄</span> Auto (fallback)
        </label>
    </div>
    <p class="hint">Auto will try your default provider first, then fall back if it fails.</p>
</div>
@else
<input type="hidden" name="provider" value="">
@endif
