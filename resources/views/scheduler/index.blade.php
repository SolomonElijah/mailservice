@extends('layouts.app')
@section('title', 'Scheduled Emails')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Scheduler ⏰</h1>
        <p class="page-subtitle">Schedule individual emails to send at a specific date and time</p>
    </div>
    <button onclick="document.getElementById('newScheduleModal').style.display='flex'" class="btn btn-primary">
        + Schedule Email
    </button>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card gold">
        <div class="stat-icon-bg">⏳</div>
        <div class="stat-label">Pending</div>
        <div class="stat-value">{{ $stats['pending'] }}</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon-bg">⚙️</div>
        <div class="stat-label">Processing</div>
        <div class="stat-value">{{ $stats['processing'] }}</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon-bg">✅</div>
        <div class="stat-label">Sent</div>
        <div class="stat-value">{{ $stats['sent'] }}</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon-bg">⚠️</div>
        <div class="stat-label">Failed</div>
        <div class="stat-value">{{ $stats['failed'] }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-icon">⏰</div>
        <div><h3>Scheduled Emails</h3><p>{{ $scheduled->total() }} total</p></div>
    </div>
    <div class="table-wrap">
        @if($scheduled->count())
        <table>
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Scheduled For</th>
                    <th>Sent At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scheduled as $s)
                @php
                    $colors = ['sent'=>'var(--green)','pending'=>'var(--gold)','processing'=>'#2563eb','failed'=>'var(--red)','cancelled'=>'var(--muted)'];
                    $bgs    = ['sent'=>'var(--green-bg)','pending'=>'var(--gold-dim)','processing'=>'#eff6ff','failed'=>'var(--red-bg)','cancelled'=>'#f3f4f6'];
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:500;font-size:13.5px;">{{ $s->to_name ?: '—' }}</div>
                        <div style="font-size:12px;color:var(--muted);">{{ $s->to_email }}</div>
                    </td>
                    <td style="font-size:13.5px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $s->subject }}
                    </td>
                    <td>
                        <span class="badge" style="background:{{ $bgs[$s->status] ?? '#f3f4f6' }};color:{{ $colors[$s->status] ?? 'var(--muted)' }};">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                    <td style="font-size:13px;white-space:nowrap;">
                        <div>{{ $s->send_at->format('M j, Y') }}</div>
                        <div style="color:var(--muted);font-size:11px;">{{ $s->send_at->format('g:i A') }}</div>
                        @if($s->status === 'pending' && $s->send_at->isPast())
                            <div style="font-size:10px;color:var(--red);font-weight:600;">OVERDUE — awaiting cron</div>
                        @elseif($s->status === 'pending')
                            <div style="font-size:10px;color:var(--muted);">{{ $s->send_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--muted);">
                        {{ $s->sent_at?->format('M j, g:i A') ?? '—' }}
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            @if($s->status === 'pending')
                            <form method="POST" action="{{ route('scheduler.cancel', $s) }}"
                                onsubmit="return confirm('Cancel this scheduled email?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-secondary btn-sm">🚫 Cancel</button>
                            </form>
                            @endif
                            @if($s->status === 'failed')
                            <form method="POST" action="{{ route('scheduler.retry', $s) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-primary btn-sm">🔄 Retry</button>
                            </form>
                            @endif
                            @if(in_array($s->status, ['sent','cancelled','failed']))
                            <form method="POST" action="{{ route('scheduler.destroy', $s) }}"
                                onsubmit="return confirm('Delete this record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                            </form>
                            @endif
                        </div>
                        @if($s->error_message)
                        <div style="font-size:11px;color:var(--red);margin-top:4px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="{{ $s->error_message }}">
                            ⚠️ {{ Str::limit($s->error_message, 40) }}
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Pagination --}}
        @if($scheduled->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <span style="font-size:13px;color:var(--muted);">
                Showing {{ $scheduled->firstItem() }}–{{ $scheduled->lastItem() }} of {{ $scheduled->total() }}
            </span>
            <div style="display:flex;gap:4px;">
                @if($scheduled->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">← Prev</span>
                @else
                    <a href="{{ $scheduled->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif
                @if($scheduled->hasMorePages())
                    <a href="{{ $scheduled->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">Next →</span>
                @endif
            </div>
        </div>
        @endif
        @else
        <div style="text-align:center;padding:60px 24px;color:var(--muted);">
            <div style="font-size:48px;margin-bottom:16px;">⏰</div>
            <h3 style="font-family:'Syne',sans-serif;font-size:18px;margin-bottom:8px;color:var(--ink);">No scheduled emails yet</h3>
            <p style="font-size:14px;margin-bottom:20px;">Schedule an email to send at a future date and time.</p>
            <button onclick="document.getElementById('newScheduleModal').style.display='flex'" class="btn btn-primary">
                + Schedule Your First Email
            </button>
        </div>
        @endif
    </div>
</div>

{{-- Info card --}}
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <div class="card-icon">ℹ️</div>
        <div><h3>How Scheduling Works</h3></div>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
        @foreach([
            ['1', '⏰', 'Schedule', 'Set a future date/time. The email is saved with status "Pending".'],
            ['2', '⚙️', 'Cron Runs', 'Every minute, the server checks for emails past their send time and dispatches queue jobs.'],
            ['3', '📤', 'Sent', 'The queue worker picks up the job, sends the email via Resend, and marks it "Sent".'],
        ] as [$n, $icon, $title, $desc])
        <div style="text-align:center;padding:16px;">
            <div style="width:48px;height:48px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 12px;">{{ $icon }}</div>
            <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;">Step {{ $n }}</div>
            <div style="font-weight:700;font-size:14px;color:var(--ink);margin-bottom:6px;">{{ $title }}</div>
            <div style="font-size:13px;color:var(--muted);line-height:1.6;">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- New Schedule Modal --}}
<div id="newScheduleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
        <div style="padding:24px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;">⏰ Schedule an Email</h3>
            <button onclick="document.getElementById('newScheduleModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted);">✕</button>
        </div>
        <div style="padding:24px 28px;">
            <form method="POST" action="{{ route('scheduler.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group">
                        <label>To Email <span class="req">*</span></label>
                        <input type="email" name="to_email" placeholder="recipient@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>To Name</label>
                        <input type="text" name="to_name" placeholder="First name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject <span class="req">*</span></label>
                    <input type="text" name="subject" placeholder="Email subject line" required>
                </div>
                <div class="form-group">
                    <label>Send At <span class="req">*</span></label>
                    <input type="datetime-local" name="send_at" required
                        min="{{ now()->addMinutes(2)->format('Y-m-d\TH:i') }}">
                    <p class="hint">Must be at least 2 minutes in the future.</p>
                </div>
                <div class="form-group">
                    <label>HTML Body <span class="req">*</span></label>
                    <textarea name="html_body" rows="8" placeholder="Full HTML email content..." required
                        style="font-family:'Courier New',monospace;font-size:12px;line-height:1.5;"></textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary">⏰ Schedule Email</button>
                    <button type="button" onclick="document.getElementById('newScheduleModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('newScheduleModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
// Auto-open if validation errors exist
@if($errors->any())
document.getElementById('newScheduleModal').style.display = 'flex';
@endif
</script>
@endpush
