@extends('layouts.app')
@section('title', 'Queue Monitor')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Queue Monitor ⚙️</h1>
        <p class="page-subtitle">Pending jobs, failed jobs and processing stats</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Admin</a>
    </div>
</div>

{{-- Throughput stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="stat-card green">
        <div class="stat-icon-bg">📤</div>
        <div class="stat-label">Processed (last hour)</div>
        <div class="stat-value">{{ number_format($processed['last_hour']) }}</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon-bg">📤</div>
        <div class="stat-label">Processed (last 24h)</div>
        <div class="stat-value">{{ number_format($processed['last_day']) }}</div>
    </div>
    <div class="stat-card {{ $failed->total() > 0 ? 'red' : 'green' }}">
        <div class="stat-icon-bg">⚠️</div>
        <div class="stat-label">Failed Jobs</div>
        <div class="stat-value">{{ $failed->total() }}</div>
    </div>
</div>

{{-- Pending queues --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div class="card-icon">⏳</div>
        <div><h3>Pending Queues</h3><p>Jobs waiting to be processed</p></div>
    </div>
    <div class="card-body" style="padding:0;">
        @forelse($pending as $q)
        <div style="padding:14px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <div>
                <code style="background:var(--gold-dim);color:var(--gold);padding:3px 10px;border-radius:6px;font-size:13px;">{{ $q->queue }}</code>
                @if($q->oldest)
                <span style="font-size:12px;color:var(--muted);margin-left:10px;">Oldest: {{ \Carbon\Carbon::createFromTimestamp($q->oldest)->diffForHumans() }}</span>
                @endif
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;">{{ number_format($q->cnt) }}</div>
        </div>
        @empty
        <div style="text-align:center;padding:32px;color:var(--muted);">
            <div style="font-size:32px;margin-bottom:8px;">✅</div>
            <p style="font-size:14px;">All queues are clear</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Failed jobs --}}
<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="card-icon">⚠️</div>
            <div><h3>Failed Jobs</h3><p>{{ $failed->total() }} total</p></div>
        </div>
        @if($failed->total() > 0)
        <form method="POST" action="{{ route('admin.queues.flush') }}"
            onsubmit="return confirm('Delete ALL failed jobs?')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">🗑 Flush All</button>
        </form>
        @endif
    </div>
    <div class="table-wrap">
        @if($failed->count())
        <table>
            <thead>
                <tr><th>Job</th><th>Queue</th><th>Exception</th><th>Failed At</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($failed as $job)
                @php $p = json_decode($job->payload); $cmd = $p->displayName ?? 'Unknown'; @endphp
                <tr>
                    <td style="font-size:13px;font-weight:600;">{{ class_basename($cmd) }}</td>
                    <td><code style="font-size:12px;background:var(--cream);padding:2px 6px;border-radius:4px;">{{ $job->queue }}</code></td>
                    <td style="font-size:12px;color:var(--red);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $job->exception }}">
                        {{ Str::limit($job->exception, 80) }}
                    </td>
                    <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($job->failed_at)->format('M j, g:i A') }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.queues.retry') }}">
                            @csrf
                            <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                            <button type="submit" class="btn btn-secondary btn-sm">🔄 Retry</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Pagination --}}
        @if($failed->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:13px;color:var(--muted);">{{ $failed->firstItem() }}–{{ $failed->lastItem() }} of {{ $failed->total() }}</span>
            <div style="display:flex;gap:4px;">
                @if($failed->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">← Prev</span>
                @else
                    <a href="{{ $failed->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif
                @if($failed->hasMorePages())
                    <a href="{{ $failed->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">Next →</span>
                @endif
            </div>
        </div>
        @endif
        @else
        <div style="text-align:center;padding:40px;color:var(--muted);">
            <div style="font-size:32px;margin-bottom:8px;">✅</div>
            <p>No failed jobs</p>
        </div>
        @endif
    </div>
</div>

{{-- Cron setup guide --}}
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <div class="card-icon">🕐</div>
        <div><h3>Cron Setup (Shared Hosting)</h3><p>Required for scheduled sending to work</p></div>
    </div>
    <div class="card-body">
        <p style="font-size:14px;color:var(--muted);margin-bottom:16px;line-height:1.7;">
            Add <strong>one cron job</strong> in cPanel → Cron Jobs. Set it to run every minute:
        </p>
        <code style="display:block;background:var(--ink);color:var(--gold);padding:16px 20px;border-radius:8px;font-size:13px;font-family:'Courier New',monospace;line-height:1.8;word-break:break-all;">
            * * * * * /usr/bin/php /home/{{ explode('.', request()->getHost())[0] ?? 'username' }}/public_html/artisan schedule:run >> /dev/null 2>&1
        </code>
        <p style="font-size:13px;color:var(--muted);margin-top:12px;line-height:1.6;">
            ⚠️ Replace <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">username</code> with your actual cPanel username and adjust the path to match where Laravel is installed.
            The scheduler will then automatically run <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">emails:process-scheduled</code> and <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;">campaigns:process-scheduled</code> every minute.
        </p>
        <div style="margin-top:16px;background:var(--gold-dim);border:1px solid var(--gold);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--ink);">
            💡 <strong>No queue worker needed on shared hosting.</strong> Since these commands run directly via cron and use synchronous mail sending (MAIL_MAILER=resend), emails are delivered without a separate worker process.
        </div>
    </div>
</div>
@endsection
