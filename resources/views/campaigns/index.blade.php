@extends('layouts.app')
@section('title', 'Campaigns')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Campaigns 📣</h1>
        <p class="page-subtitle">Create, schedule and send email campaigns to your contact lists</p>
    </div>
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
</div>

{{-- Stats row --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card gold">
        <div class="stat-icon-bg">📣</div>
        <div class="stat-label">Total</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon-bg">✅</div>
        <div class="stat-label">Sent</div>
        <div class="stat-value">{{ $stats['sent'] }}</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon-bg">📅</div>
        <div class="stat-label">Scheduled</div>
        <div class="stat-value">{{ $stats['scheduled'] }}</div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--muted);">
        <div class="stat-icon-bg">📝</div>
        <div class="stat-label">Drafts</div>
        <div class="stat-value">{{ $stats['draft'] }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-icon">📣</div>
        <div><h3>All Campaigns</h3><p>{{ $campaigns->total() }} campaigns total</p></div>
    </div>
    <div class="table-wrap">
        @if($campaigns->count())
        <table>
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Open Rate</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13.5px;">{{ $c->name }}</div>
                        <div style="font-size:12px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;">{{ $c->subject }}</div>
                    </td>
                    <td>
                        @php $type = \App\Models\Campaign::types()[$c->type] ?? ['icon'=>'✉️','label'=>$c->type]; @endphp
                        <span style="font-size:13px;">{{ $type['icon'] }} {{ $type['label'] }}</span>
                    </td>
                    <td>
                        @php
                            $colors = ['sent'=>'green','sending'=>'blue','scheduled'=>'gold','draft'=>'muted','failed'=>'red','paused'=>'orange'];
                            $sc = $colors[$c->status] ?? 'muted';
                            $styles = ['green'=>'background:var(--green-bg);color:var(--green)','blue'=>'background:var(--blue-bg);color:var(--blue)','gold'=>'background:var(--gold-dim);color:var(--gold)','muted'=>'background:#f3f4f6;color:#6b7280','red'=>'background:var(--red-bg);color:var(--red)'];
                        @endphp
                        <span class="badge" style="{{ $styles[$sc] ?? $styles['muted'] }}">{{ ucfirst($c->status) }}</span>
                    </td>
                    <td style="font-family:'Syne',sans-serif;font-weight:700;">{{ number_format($c->total_recipients) }}</td>
                    <td>
                        @if($c->sent_count > 0)
                            <span style="color:var(--green);font-weight:600;">{{ $c->sent_count }}</span>
                            @if($c->failed_count > 0)
                                <span style="color:var(--red);font-size:12px;"> / {{ $c->failed_count }} failed</span>
                            @endif
                        @else
                            <span style="color:var(--muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($c->status === 'sent')
                            <span style="font-weight:700;color:var(--ink);">{{ $c->open_rate }}%</span>
                        @else
                            <span style="color:var(--muted);">—</span>
                        @endif
                    </td>
                    <td style="color:var(--muted);font-size:12px;white-space:nowrap;">
                        {{ $c->sent_at ? $c->sent_at->format('M j, Y') : $c->created_at->format('M j, Y') }}
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <a href="{{ route('campaigns.show', $c) }}" class="btn btn-secondary btn-sm">📊</a>
                            @if(!in_array($c->status, ['sent','sending']))
                                <a href="{{ route('campaigns.edit', $c) }}" class="btn btn-secondary btn-sm">✏️</a>
                                @if($c->contact_list_id)
                                <form method="POST" action="{{ route('campaigns.send', $c) }}"
                                    onsubmit="return confirm('Send this campaign now to all subscribers?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">🚀 Send</button>
                                </form>
                                @endif
                            @endif
                            <form method="POST" action="{{ route('campaigns.duplicate', $c) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">📋</button>
                            </form>
                            <form method="POST" action="{{ route('campaigns.destroy', $c) }}"
                                onsubmit="return confirm('Delete this campaign?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- Pagination --}}
        @if($campaigns->hasPages())
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <span style="font-size:13px;color:var(--muted);">Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} of {{ $campaigns->total() }}</span>
            <div style="display:flex;gap:4px;">
                @if($campaigns->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">← Prev</span>
                @else
                    <a href="{{ $campaigns->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif
                @if($campaigns->hasMorePages())
                    <a href="{{ $campaigns->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">Next →</span>
                @endif
            </div>
        </div>
        @endif
        @else
        <div style="text-align:center;padding:60px 24px;color:var(--muted);">
            <div style="font-size:48px;margin-bottom:16px;">📣</div>
            <h3 style="font-family:'Syne',sans-serif;font-size:18px;margin-bottom:8px;color:var(--ink);">No campaigns yet</h3>
            <p style="font-size:14px;margin-bottom:24px;">Create your first campaign and start reaching your audience.</p>
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ Create Campaign</a>
        </div>
        @endif
    </div>
</div>
@endsection
