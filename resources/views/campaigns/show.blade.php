@extends('layouts.app')
@section('title', $campaign->name)

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">{{ $campaign->name }}</h1>
        <p class="page-subtitle">Campaign report & details</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if(!in_array($campaign->status, ['sent','sending']))
            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-secondary">✏️ Edit</a>
        @endif
        <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">← Back</a>
    </div>
</div>

{{-- Status banner --}}
@php
    $banners = ['sent'=>['bg'=>'var(--green-bg)','color'=>'var(--green)','icon'=>'✅'],'sending'=>['bg'=>'var(--blue-bg)','color'=>'var(--blue)','icon'=>'⏳'],'scheduled'=>['bg'=>'var(--gold-dim)','color'=>'var(--gold)','icon'=>'📅'],'draft'=>['bg'=>'#f3f4f6','color'=>'#6b7280','icon'=>'📝'],'failed'=>['bg'=>'var(--red-bg)','color'=>'var(--red)','icon'=>'❌']];
    $b = $banners[$campaign->status] ?? $banners['draft'];
@endphp
<div style="background:{{ $b['bg'] }};border-radius:10px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-size:14px;color:{{ $b['color'] }};font-weight:600;">
    <span style="font-size:18px;">{{ $b['icon'] }}</span>
    Campaign is <strong>{{ ucfirst($campaign->status) }}</strong>
    @if($campaign->sent_at) · Sent {{ $campaign->sent_at->format('M j, Y \a\t g:i A') }} @endif
    @if($campaign->scheduled_at && $campaign->status === 'scheduled') · Scheduled for {{ $campaign->scheduled_at->format('M j, Y \a\t g:i A') }} @endif
</div>

{{-- Stats cards --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card gold">
        <div class="stat-icon-bg">👥</div>
        <div class="stat-label">Recipients</div>
        <div class="stat-value">{{ number_format($campaign->total_recipients) }}</div>
        <div class="stat-meta">Total contacts targeted</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon-bg">📤</div>
        <div class="stat-label">Delivered</div>
        <div class="stat-value">{{ number_format($campaign->sent_count) }}</div>
        <div class="stat-meta">{{ $campaign->failed_count }} failed</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon-bg">👁</div>
        <div class="stat-label">Open Rate</div>
        <div class="stat-value">{{ $campaign->open_rate }}%</div>
        <div class="stat-meta">{{ $campaign->opened_count }} opens</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #7c3aed;">
        <div class="stat-icon-bg">🖱️</div>
        <div class="stat-label">Click Rate</div>
        <div class="stat-value">{{ $campaign->click_rate }}%</div>
        <div class="stat-meta">{{ $campaign->clicked_count }} clicks</div>
    </div>
</div>

{{-- Details --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📋</div>
            <div><h3>Campaign Info</h3></div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach([
                ['Subject',      $campaign->subject],
                ['Type',         ucfirst($campaign->type)],
                ['From',         ($campaign->from_name ?? config('app.name')) . ' <' . ($campaign->from_email ?? config('mail.from.address')) . '>'],
                ['Contact List', $campaign->contactList?->name ?? '—'],
                ['Template',     $campaign->template?->name ?? 'Custom'],
                ['Created',      $campaign->created_at->format('M j, Y g:i A')],
            ] as [$label, $value])
            <div style="display:flex;padding:12px 20px;border-bottom:1px solid var(--border);font-size:13.5px;">
                <span style="width:130px;color:var(--muted);flex-shrink:0;">{{ $label }}</span>
                <span style="font-weight:500;">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Preview --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👁</div>
            <div><h3>Email Preview</h3></div>
        </div>
        <div style="height:340px;overflow:hidden;border-bottom-left-radius:12px;border-bottom-right-radius:12px;">
            <iframe id="previewFrame" style="width:100%;height:100%;border:none;"></iframe>
        </div>
    </div>
</div>

@if($campaign->status === 'draft' && $campaign->contact_list_id)
<div style="margin-top:20px;text-align:center;">
    <form method="POST" action="{{ route('campaigns.send', $campaign) }}"
        onsubmit="return confirm('Send this campaign to all subscribers now?')">
        @csrf
        <button type="submit" class="btn btn-primary" style="font-size:16px;padding:16px 40px;">
            🚀 Send Campaign Now
        </button>
    </form>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const html = {!! json_encode($campaign->html_content) !!};
    const frame = document.getElementById('previewFrame');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
});
</script>
@endpush
