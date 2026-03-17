@extends('layouts.app')

@section('title', 'Email Logs')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Email Logs 📋</h1>
        <p class="page-subtitle">Full history of all emails sent from your account</p>
    </div>
    <a href="{{ route('email.index') }}" class="btn btn-primary">✉️ Send Email</a>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" action="{{ route('logs.index') }}">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
                <div>
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Email address or subject...">
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="">All statuses</option>
                        <option value="sent"    {{ request('status') === 'sent'    ? 'selected' : '' }}>Sent</option>
                        <option value="opened"  {{ request('status') === 'opened'  ? 'selected' : '' }}>Opened</option>
                        <option value="clicked" {{ request('status') === 'clicked' ? 'selected' : '' }}>Clicked</option>
                        <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Failed</option>
                        <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>Bounced</option>
                    </select>
                </div>
                <div>
                    <label>Type</label>
                    <select name="type">
                        <option value="">All types</option>
                        <option value="single"   {{ request('type') === 'single'   ? 'selected' : '' }}>Single</option>
                        <option value="multiple" {{ request('type') === 'multiple' ? 'selected' : '' }}>Multiple</option>
                        <option value="advanced" {{ request('type') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                        <option value="campaign" {{ request('type') === 'campaign' ? 'selected' : '' }}>Campaign</option>
                    </select>
                </div>
                <div>
                    <label>Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div style="display:flex;gap:8px;padding-top:4px;">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('logs.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <div class="card-icon">📋</div>
        <div>
            <h3>Email History</h3>
            <p>{{ $logs->total() }} record(s) found</p>
        </div>
    </div>
    <div class="table-wrap">
        @if($logs->count())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Opened</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="td-muted">{{ $log->id }}</td>
                    <td>
                        <div style="font-weight:500;font-size:13px;">{{ $log->recipient_email }}</div>
                        @if($log->recipient_name)
                            <div class="td-muted">{{ $log->recipient_name }}</div>
                        @endif
                    </td>
                    <td style="max-width:200px;">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;">
                            {{ $log->subject }}
                        </div>
                        @if($log->error_message)
                            <div style="font-size:11px;color:var(--red);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ Str::limit($log->error_message, 60) }}
                            </div>
                        @endif
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
                    <td class="td-muted">
                        {{ $log->opened_at ? $log->opened_at->format('M j, g:i A') : '—' }}
                    </td>
                    <td class="td-muted" style="white-space:nowrap;">
                        {{ $log->created_at->format('M j, Y') }}<br>
                        <span style="font-size:11px;">{{ $log->created_at->format('g:i A') }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="font-size:13px;color:var(--muted);">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} results
            </div>
            <div style="display:flex;gap:4px;">
                @if($logs->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:0.4;cursor:default;">← Prev</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif

                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:0.4;cursor:default;">Next →</span>
                @endif
            </div>
        </div>
        @endif

        @else
        <div style="text-align:center;padding:60px 24px;color:var(--muted);">
            <div style="font-size:40px;margin-bottom:12px;">📭</div>
            <p style="font-size:15px;margin-bottom:8px;">No email logs found</p>
            <p style="font-size:13px;">
                @if(request()->hasAny(['search','status','type','date_from']))
                    Try adjusting your filters or <a href="{{ route('logs.index') }}" style="color:var(--gold);">clear all</a>
                @else
                    <a href="{{ route('email.index') }}" style="color:var(--gold);">Send your first email →</a>
                @endif
            </p>
        </div>
        @endif
    </div>
</div>

@endsection
