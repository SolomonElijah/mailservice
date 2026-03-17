@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')

@if(session('admin_impersonating'))
<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;font-size:14px;">
    <span>⚠️ <strong>Impersonating a user.</strong> Actions affect their account.</span>
    <form method="POST" action="{{ route('admin.stop-impersonating') }}">@csrf
        <button type="submit" class="btn btn-secondary btn-sm">🔙 Return to Admin</button>
    </form>
</div>
@endif

<div class="page-header">
    <h1 class="page-title">Admin Panel ⚙️</h1>
    <p class="page-subtitle">System overview, user management and queue monitor</p>
</div>

{{-- ── System Stats ── --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    @foreach([
        ['👥','Users',        $stats['users'],       'gold'],
        ['📤','Emails Sent',  $stats['emails_sent'], 'green'],
        ['📣','Campaigns',    $stats['campaigns'],   'blue'],
        ['⏳','Pending Jobs', $stats['scheduled'],   'gold'],
    ] as [$icon,$label,$val,$color])
    <div class="stat-card {{ $color }}">
        <div class="stat-icon-bg">{{ $icon }}</div>
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value">{{ number_format($val) }}</div>
    </div>
    @endforeach
</div>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    @foreach([
        ['👤','Contacts',   $stats['contacts'],  'muted'],
        ['🎨','Templates',  $stats['templates'], 'muted'],
        ['📋','Lists',      $stats['lists'],     'muted'],
        ['⚠️','Failed Emails',$stats['failed'], 'red'],
    ] as [$icon,$label,$val,$color])
    <div class="stat-card {{ $color }}">
        <div class="stat-icon-bg">{{ $icon }}</div>
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value">{{ number_format($val) }}</div>
    </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

    {{-- Activity Chart --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📈</div>
            <div><h3>Email Activity</h3><p>Last 14 days</p></div>
        </div>
        <div style="padding:20px;">
            <canvas id="activityChart" height="180"></canvas>
        </div>
    </div>

    {{-- Top Senders --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🏆</div>
            <div><h3>Top Senders</h3><p>By email volume</p></div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach($topSenders as $i => $u)
            <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
                <div style="width:28px;height:28px;border-radius:8px;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--gold);">{{ $i+1 }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->name }}</div>
                    <div style="font-size:11px;color:var(--muted);">{{ $u->email }}</div>
                </div>
                <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:14px;">{{ number_format($u->sent_count) }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

    {{-- Queue Status --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">⚙️</div>
            <div><h3>Queue Status</h3><p>{{ $queueDepth }} jobs pending</p></div>
            <div class="card-header-action">
                <a href="{{ route('admin.queues') }}" class="btn btn-secondary btn-sm">View All</a>
            </div>
        </div>
        <div class="card-body" style="padding:0;">
            @forelse($pendingJobs as $q)
            <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:13.5px;">
                <div>
                    <span style="font-family:'Courier New',monospace;background:var(--gold-dim);color:var(--gold);padding:2px 8px;border-radius:4px;font-size:12px;">{{ $q->queue }}</span>
                </div>
                <span style="font-weight:700;font-family:'Syne',sans-serif;">{{ number_format($q->cnt) }} jobs</span>
            </div>
            @empty
            <div style="text-align:center;padding:28px;color:var(--muted);font-size:13px;">✅ No pending jobs</div>
            @endforelse
        </div>
    </div>

    {{-- Recent Webhook Events --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔔</div>
            <div><h3>Recent Webhooks</h3></div>
        </div>
        <div class="card-body" style="padding:0;max-height:280px;overflow-y:auto;">
            @forelse($recentWebhooks as $ev)
            @php
                $icons = ['email.delivered'=>'🟢','email.opened'=>'👁','email.clicked'=>'🖱️','email.bounced'=>'⛔','email.complained'=>'🚨'];
                $icon  = $icons[$ev->event_type] ?? '📨';
            @endphp
            <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;font-size:13px;">
                <span>{{ $icon }}</span>
                <span style="flex:1;color:var(--muted);">{{ str_replace('email.','',$ev->event_type) }}</span>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;font-size:12px;">{{ $ev->recipient_email ?? '—' }}</span>
                <span style="font-size:11px;color:var(--muted);white-space:nowrap;">{{ $ev->created_at->diffForHumans(null,true) }}</span>
            </div>
            @empty
            <div style="text-align:center;padding:28px;color:var(--muted);font-size:13px;">No webhook events yet</div>
            @endforelse
        </div>
    </div>

</div>

{{-- Failed Jobs --}}
@if($failedJobs->count())
<div class="card">
    <div class="card-header">
        <div class="card-icon">⚠️</div>
        <div><h3>Failed Jobs</h3><p>{{ $failedJobs->count() }} failed</p></div>
        <div class="card-header-action">
            <a href="{{ route('admin.queues') }}" class="btn btn-secondary btn-sm">Manage →</a>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Queue</th><th>Payload</th><th>Failed At</th></tr>
            </thead>
            <tbody>
                @foreach($failedJobs->take(5) as $job)
                @php $p = json_decode($job->payload); $cmd = $p->displayName ?? 'Unknown'; @endphp
                <tr>
                    <td><code style="font-size:12px;">{{ $job->queue }}</code></td>
                    <td style="font-size:13px;color:var(--muted);">{{ $cmd }}</td>
                    <td style="font-size:12px;color:var(--muted);">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const raw   = @json($activity);
    const days  = 14;
    const labels = [], data = [];

    for (let i = days - 1; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0,10);
        labels.push(d.toLocaleDateString('en', {month:'short', day:'numeric'}));
        data.push(raw[key] ?? 0);
    }

    new Chart(document.getElementById('activityChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Emails Sent',
                data,
                backgroundColor: 'rgba(212,168,67,.7)',
                borderColor: '#d4a843',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f0ede8' } },
            }
        }
    });
});
</script>
@endpush
