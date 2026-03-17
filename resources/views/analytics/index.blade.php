@extends('layouts.app')
@section('title', 'Analytics')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Analytics 📈</h1>
        <p class="page-subtitle">Open rates, click tracking and campaign performance</p>
    </div>
    {{-- Date range picker --}}
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        @foreach([7=>'7d', 14=>'14d', 30=>'30d', 90=>'90d'] as $days => $label)
        <a href="{{ route('analytics.index', ['range' => $days]) }}"
           class="btn {{ $range == $days ? 'btn-primary' : 'btn-secondary' }} btn-sm">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- ── Overview Stats ── --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card gold">
        <div class="stat-icon-bg">📤</div>
        <div class="stat-label">Total Sent</div>
        <div class="stat-value">{{ number_format($totalSent) }}</div>
        <div class="stat-meta">Last {{ $range }} days</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon-bg">👁</div>
        <div class="stat-label">Open Rate</div>
        <div class="stat-value">{{ $openRate }}%</div>
        <div class="stat-meta">{{ number_format($totalOpened) }} opens</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon-bg">🖱️</div>
        <div class="stat-label">Click Rate</div>
        <div class="stat-value">{{ $clickRate }}%</div>
        <div class="stat-meta">{{ number_format($totalClicked) }} clicks</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon-bg">⚠️</div>
        <div class="stat-label">Failed</div>
        <div class="stat-value">{{ $failRate }}%</div>
        <div class="stat-meta">{{ number_format($totalFailed) }} emails</div>
    </div>
</div>

{{-- ── Secondary Metrics ── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    @foreach([
        ['Click-to-Open Rate', $ctr . '%',      'Of openers who clicked',  '#7c3aed'],
        ['Deliverability',     (100 - $failRate) . '%', 'Success rate',    'var(--green)'],
        ['Engagement Score',   $openRate > 20 ? 'Excellent 🔥' : ($openRate > 10 ? 'Good 👍' : 'Low ⚠️'), 'Based on open rate', 'var(--gold)'],
    ] as [$label, $val, $sub, $color])
    <div class="card" style="padding:20px 24px;">
        <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">{{ $label }}</div>
        <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">{{ $sub }}</div>
    </div>
    @endforeach
</div>

{{-- ── Chart Row ── --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

    {{-- Line chart: sent / opened / clicked over time --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📈</div>
            <div><h3>Email Activity</h3><p>Last {{ $range }} days</p></div>
        </div>
        <div style="padding:20px;">
            <canvas id="activityChart" height="220"></canvas>
        </div>
    </div>

    {{-- Funnel --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🎯</div>
            <div><h3>Engagement Funnel</h3></div>
        </div>
        <div style="padding:24px 20px;display:flex;flex-direction:column;gap:10px;">
            @php
                $funnel = [
                    ['Sent',    $totalSent,    $totalSent > 0 ? 100 : 0,                                  'var(--ink)'],
                    ['Opened',  $totalOpened,  $totalSent > 0 ? round($totalOpened/$totalSent*100) : 0,   'var(--gold)'],
                    ['Clicked', $totalClicked, $totalSent > 0 ? round($totalClicked/$totalSent*100) : 0,  'var(--green)'],
                    ['Failed',  $totalFailed,  $totalSent > 0 ? round($totalFailed/$totalSent*100) : 0,   'var(--red)'],
                ];
            @endphp
            @foreach($funnel as [$label, $count, $pct, $color])
            <div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                    <span style="color:var(--muted);">{{ $label }}</span>
                    <span style="font-weight:700;color:{{ $color }};">{{ number_format($count) }} <span style="font-size:11px;opacity:.6;">({{ $pct }}%)</span></span>
                </div>
                <div style="height:8px;background:var(--border);border-radius:8px;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:8px;transition:width .6s;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ── Campaign Performance Table ── --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div class="card-icon">📣</div>
        <div><h3>Campaign Performance</h3><p>Sent campaigns with tracking data</p></div>
    </div>
    <div class="table-wrap">
        @if($campaigns->count())
        <table>
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Sent</th>
                    <th>Delivered</th>
                    <th>Opens</th>
                    <th>Open Rate</th>
                    <th>Clicks</th>
                    <th>Click Rate</th>
                    <th>Failed</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr>
                    <td>
                        <a href="{{ route('campaigns.show', $c) }}"
                           style="font-weight:600;color:var(--ink);text-decoration:none;font-size:13.5px;">
                            {{ $c->name }}
                        </a>
                    </td>
                    <td style="font-family:'Syne',sans-serif;font-weight:700;">{{ number_format($c->total_recipients) }}</td>
                    <td style="color:var(--green);font-weight:600;">{{ number_format($c->sent_count) }}</td>
                    <td>{{ number_format($c->opened_count) }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:50px;height:6px;background:var(--border);border-radius:6px;overflow:hidden;">
                                <div style="height:100%;width:{{ min($c->open_rate, 100) }}%;background:var(--gold);border-radius:6px;"></div>
                            </div>
                            <span style="font-weight:700;font-size:13px;">{{ $c->open_rate }}%</span>
                        </div>
                    </td>
                    <td>{{ number_format($c->clicked_count) }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:50px;height:6px;background:var(--border);border-radius:6px;overflow:hidden;">
                                <div style="height:100%;width:{{ min($c->click_rate, 100) }}%;background:var(--green);border-radius:6px;"></div>
                            </div>
                            <span style="font-weight:700;font-size:13px;">{{ $c->click_rate }}%</span>
                        </div>
                    </td>
                    <td style="color:{{ $c->failed_count > 0 ? 'var(--red)' : 'var(--muted)' }};font-weight:{{ $c->failed_count > 0 ? '600' : '400' }};">
                        {{ number_format($c->failed_count) }}
                    </td>
                    <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                        {{ $c->sent_at?->format('M j, Y') ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align:center;padding:40px;color:var(--muted);">
            <p>No sent campaigns yet. <a href="{{ route('campaigns.create') }}" style="color:var(--gold);">Create one →</a></p>
        </div>
        @endif
    </div>
</div>

{{-- ── Bottom Row ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">

    {{-- Top Clicked Links --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔗</div>
            <div><h3>Top Clicked Links</h3><p>Last {{ $range }} days</p></div>
        </div>
        <div class="card-body" style="padding:0;">
            @forelse($topLinks as $link)
            <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $link->original_url }}">
                        {{ Str::limit($link->original_url, 40) }}
                    </div>
                </div>
                <span style="background:var(--gold-dim);color:var(--gold);font-size:12px;font-weight:700;padding:2px 8px;border-radius:100px;white-space:nowrap;">
                    {{ $link->clicks }} clicks
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px;">No click data yet</div>
            @endforelse
        </div>
    </div>

    {{-- Recent Opens --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👁</div>
            <div><h3>Recent Opens</h3></div>
        </div>
        <div class="card-body" style="padding:0;">
            @forelse($recentOpens as $log)
            <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                <div style="width:30px;height:30px;border-radius:8px;background:var(--green-bg);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">👁</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->recipient_email }}</div>
                    <div style="font-size:11px;color:var(--muted);">{{ $log->opened_at?->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px;">No opens tracked yet</div>
            @endforelse
        </div>
    </div>

    {{-- Webhook Events --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔔</div>
            <div><h3>Webhook Events</h3><p>From Resend</p></div>
        </div>
        <div class="card-body" style="padding:0;">
            @forelse($webhookEvents as $event)
            @php
                $icons = [
                    'email.delivered'        => ['🟢', 'var(--green)'],
                    'email.opened'           => ['👁',  'var(--gold)'],
                    'email.clicked'          => ['🖱️', '#7c3aed'],
                    'email.bounced'          => ['⛔', 'var(--red)'],
                    'email.complained'       => ['🚨', 'var(--red)'],
                    'email.delivery_delayed' => ['⏳', '#f59e0b'],
                ];
                [$icon, $color] = $icons[$event->event_type] ?? ['📨', 'var(--muted)'];
                $shortType = str_replace('email.', '', $event->event_type);
            @endphp
            <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                <span style="font-size:16px;">{{ $icon }}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:700;color:{{ $color }};text-transform:capitalize;">{{ $shortType }}</div>
                    <div style="font-size:11px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $event->recipient_email ?? 'unknown' }}</div>
                </div>
                <div style="font-size:10px;color:var(--muted);white-space:nowrap;">{{ $event->created_at->diffForHumans(null, true) }}</div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px;">
                No webhook events yet.<br>
                <a href="{{ route('analytics.webhook-setup') }}" style="color:var(--gold);font-size:12px;">Setup guide →</a>
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels  = @json($chartLabels);
    const sent    = @json($sentData);
    const opened  = @json($openedData);
    const clicked = @json($clickedData);

    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Sent',
                    data: sent,
                    borderColor: '#0d0d14',
                    backgroundColor: 'rgba(13,13,20,.05)',
                    borderWidth: 2,
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Opened',
                    data: opened,
                    borderColor: '#d4a843',
                    backgroundColor: 'rgba(212,168,67,.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Clicked',
                    data: clicked,
                    borderColor: '#1e7e52',
                    backgroundColor: 'rgba(30,126,82,.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16, font: { size: 12 } },
                },
                tooltip: { padding: 12, cornerRadius: 8 },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0ede8' },
                    ticks: { precision: 0, font: { size: 11 } },
                },
            },
        },
    });
});
</script>
@endpush
