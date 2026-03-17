@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="page-header">
    <h1 class="page-title">Dashboard 📊</h1>
    <p class="page-subtitle">Welcome back, {{ Auth::user()->name }}! Here's your email activity overview.</p>
</div>

{{-- ══ STAT CARDS ══ --}}
<div class="stats-grid">

    <div class="stat-card gold">
        <div class="stat-icon-bg">✉️</div>
        <div class="stat-label">Total Sent</div>
        <div class="stat-value">{{ number_format($totalSent) }}</div>
        <div class="stat-meta">
            <span>{{ $sentToday }} today</span>
            &nbsp;·&nbsp;
            <span>{{ $sentThisWeek }} this week</span>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon-bg">📂</div>
        <div class="stat-label">This Month</div>
        <div class="stat-value">{{ number_format($sentThisMonth) }}</div>
        <div class="stat-meta">
            @if($monthlyGrowth > 0)
                <span class="up">▲ {{ $monthlyGrowth }}% vs last month</span>
            @elseif($monthlyGrowth < 0)
                <span class="down">▼ {{ abs($monthlyGrowth) }}% vs last month</span>
            @else
                <span>Same as last month</span>
            @endif
        </div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon-bg">👁</div>
        <div class="stat-label">Open Rate</div>
        <div class="stat-value">{{ $openRate }}%</div>
        <div class="stat-meta">
            {{ number_format($totalOpened) }} emails opened
        </div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon-bg">❌</div>
        <div class="stat-label">Failed</div>
        <div class="stat-value">{{ number_format($totalFailed) }}</div>
        <div class="stat-meta">
            {{ $failureRate }}% failure rate
        </div>
    </div>

</div>

{{-- ══ CHARTS ══ --}}
<div class="charts-grid">

    {{-- Line Chart: 7-day activity --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📈</div>
            <div>
                <h3>Email Activity</h3>
                <p>Sent vs Opened — last 7 days</p>
            </div>
            <div class="card-header-action">
                <select id="chartRange" onchange="switchChart(this.value)"
                    style="padding:6px 10px;font-size:12px;border:1.5px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;outline:none;">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <canvas id="activityChart" height="100"></canvas>
        </div>
    </div>

    {{-- Doughnut: Email type breakdown --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🍩</div>
            <div>
                <h3>Email Types</h3>
                <p>Breakdown by send type</p>
            </div>
        </div>
        <div class="card-body">
            @if($totalSent > 0)
                <canvas id="typeChart" height="160"></canvas>
                <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach(['single'=>'#d4a843','multiple'=>'#0d0d14','advanced'=>'#2563eb','campaign'=>'#1e7e52'] as $type => $color)
                        @if(isset($typeBreakdown[$type]) && $typeBreakdown[$type] > 0)
                        <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--muted);">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $color }};display:inline-block;"></span>
                            {{ ucfirst($type) }}: <strong style="color:var(--ink);">{{ $typeBreakdown[$type] }}</strong>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div style="text-align:center;padding:40px 0;color:var(--muted);">
                    <div style="font-size:36px;margin-bottom:10px;">📭</div>
                    <p style="font-size:14px;">No emails sent yet</p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ══ BOTTOM ROW ══ --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">

    {{-- Recent Activity Table --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🕐</div>
            <div>
                <h3>Recent Activity</h3>
                <p>Last 10 emails sent</p>
            </div>
            <div class="card-header-action">
                <a href="{{ route('logs.index') }}" class="btn btn-secondary btn-sm">View All</a>
            </div>
        </div>
        <div class="table-wrap">
            @if($recentLogs->count())
            <table>
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td>
                            <div style="font-weight:500;font-size:13px;">{{ $log->recipient_email }}</div>
                            @if($log->recipient_name)
                                <div class="td-muted">{{ $log->recipient_name }}</div>
                            @endif
                        </td>
                        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $log->subject }}
                        </td>
                        <td>
                            <span class="badge" style="background:var(--gold-dim);color:var(--gold);">
                                {{ ucfirst($log->type) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $log->status }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="td-muted">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="text-align:center;padding:40px;color:var(--muted);">
                <div style="font-size:32px;margin-bottom:10px;">📭</div>
                <p style="font-size:14px;">No emails sent yet. <a href="{{ route('email.index') }}" style="color:var(--gold);">Send your first email →</a></p>
            </div>
            @endif
        </div>
    </div>

    {{-- Top Recipients + Quick Stats --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Quick Stats --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">⚡</div>
                <div><h3>Quick Stats</h3></div>
            </div>
            <div class="card-body" style="padding:16px;">
                @foreach([
                    ['label' => 'Click Rate',    'value' => $clickRate . '%',               'icon' => '🖱️'],
                    ['label' => 'Sent Today',    'value' => $sentToday,                     'icon' => '📤'],
                    ['label' => 'Sent This Week','value' => $sentThisWeek,                  'icon' => '📅'],
                    ['label' => 'Total Opened',  'value' => number_format($totalOpened),    'icon' => '👁'],
                ] as $stat)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);">
                        <span>{{ $stat['icon'] }}</span> {{ $stat['label'] }}
                    </div>
                    <strong style="font-family:'Syne',sans-serif;font-size:15px;color:var(--ink);">{{ $stat['value'] }}</strong>
                </div>
                @endforeach
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);">
                        <span>📊</span> Last Month
                    </div>
                    <strong style="font-family:'Syne',sans-serif;font-size:15px;color:var(--ink);">{{ number_format($lastMonthCount) }}</strong>
                </div>
            </div>
        </div>

        {{-- Top Recipients --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🏆</div>
                <div><h3>Top Recipients</h3></div>
            </div>
            <div class="card-body" style="padding:16px;">
                @forelse($topRecipients as $i => $r)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                    <div style="width:22px;height:22px;border-radius:50%;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--gold);flex-shrink:0;">{{ $i+1 }}</div>
                    <div style="flex:1;overflow:hidden;">
                        <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r->recipient_email }}</div>
                    </div>
                    <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--ink);">{{ $r->count }}</div>
                </div>
                @empty
                <p style="font-size:13px;color:var(--muted);text-align:center;padding:16px 0;">No data yet</p>
                @endforelse
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
// ── Chart Data ──
const data7 = {
    labels: {!! json_encode($last7Days->pluck('date')) !!},
    sent:   {!! json_encode($last7Days->pluck('sent')) !!},
    opened: {!! json_encode($last7Days->pluck('opened')) !!},
};
const data30 = {
    labels: {!! json_encode($last30Days->pluck('date')) !!},
    sent:   {!! json_encode($last30Days->pluck('sent')) !!},
};

// ── Activity Line Chart ──
const ctx = document.getElementById('activityChart').getContext('2d');
let activityChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: data7.labels,
        datasets: [
            {
                label: 'Sent',
                data: data7.sent,
                borderColor: '#0d0d14',
                backgroundColor: 'rgba(13,13,20,0.05)',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#0d0d14',
                pointRadius: 4,
            },
            {
                label: 'Opened',
                data: data7.opened,
                borderColor: '#d4a843',
                backgroundColor: 'rgba(212,168,67,0.08)',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#d4a843',
                pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { family: 'DM Sans', size: 12 }, boxWidth: 12 } },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 11 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'DM Sans', size: 11 }, stepSize: 1 } }
        }
    }
});

function switchChart(range) {
    if (range === '30') {
        activityChart.data.labels = data30.labels;
        activityChart.data.datasets[0].data = data30.sent;
        activityChart.data.datasets[1].data = new Array(30).fill(0);
    } else {
        activityChart.data.labels = data7.labels;
        activityChart.data.datasets[0].data = data7.sent;
        activityChart.data.datasets[1].data = data7.opened;
    }
    activityChart.update();
}

// ── Type Doughnut Chart ──
@if($totalSent > 0)
const typeData = @json($typeBreakdown);
const typeLabels = Object.keys(typeData).map(k => k.charAt(0).toUpperCase() + k.slice(1));
const typeValues = Object.values(typeData);
const typeColors = ['#d4a843', '#0d0d14', '#2563eb', '#1e7e52'];

new Chart(document.getElementById('typeChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeValues,
            backgroundColor: typeColors.slice(0, typeValues.length),
            borderWidth: 3,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} emails` } }
        }
    }
});
@endif
</script>
@endpush
