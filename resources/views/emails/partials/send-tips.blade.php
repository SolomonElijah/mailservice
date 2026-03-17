{{-- Send tips sidebar --}}
<div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:72px;">

    {{-- Quick stats --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📊</div>
            <div><h3>Today's Activity</h3></div>
        </div>
        <div class="card-body" style="padding:0;">
            @php
                $todaySent   = \App\Models\EmailLog::where('user_id', auth()->id())->whereDate('created_at', today())->where('status','sent')->count();
                $todayFailed = \App\Models\EmailLog::where('user_id', auth()->id())->whereDate('created_at', today())->where('status','failed')->count();
                $totalSent   = \App\Models\EmailLog::where('user_id', auth()->id())->where('status','sent')->count();
            @endphp
            @foreach([
                ['Sent today',  $todaySent,   'var(--green)'],
                ['Failed today',$todayFailed, 'var(--red)'],
                ['Total sent',  $totalSent,   'var(--ink)'],
            ] as [$label, $val, $color])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-bottom:1px solid var(--border);font-size:13.5px;">
                <span style="color:var(--muted);">{{ $label }}</span>
                <strong style="font-family:'Syne',sans-serif;font-size:16px;color:{{ $color }};">{{ number_format($val) }}</strong>
            </div>
            @endforeach
            <div style="padding:12px 20px;">
                <a href="{{ route('logs.index') }}" style="font-size:13px;color:var(--gold);font-weight:600;text-decoration:none;">View all logs →</a>
            </div>
        </div>
    </div>

    {{-- Provider status --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📡</div>
            <div><h3>Providers</h3><p>Configured senders</p></div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach(config('providers.providers', []) as $key => $p)
            <div style="display:flex;align-items:center;gap:10px;padding:11px 20px;border-bottom:1px solid var(--border);">
                <span style="font-size:18px;">{{ $p['icon'] }}</span>
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:600;">{{ $p['label'] }}</div>
                    @if(config('providers.default') === $key)
                    <div style="font-size:10px;color:var(--gold);font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Default</div>
                    @endif
                </div>
                <span style="width:8px;height:8px;border-radius:50%;background:{{ ($p['enabled'] ?? false) ? 'var(--green)' : '#d1d5db' }};display:inline-block;"></span>
            </div>
            @endforeach
            <div style="padding:12px 20px;">
                <a href="{{ route('providers.index') }}" style="font-size:13px;color:var(--gold);font-weight:600;text-decoration:none;">Manage providers →</a>
            </div>
        </div>
    </div>

    {{-- Tips --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">💡</div>
            <div><h3>Tips</h3></div>
        </div>
        <div class="card-body" style="padding:16px 20px;">
            @foreach([
                ['📣', 'Use Campaigns for bulk sends to contact lists with tracking.'],
                ['🎨', 'Build reusable HTML templates under Templates.'],
                ['⏰', 'Schedule emails to send at a specific time via Scheduler.'],
                ['📊', 'Track opens and clicks in Analytics.'],
            ] as [$icon, $tip])
            <div style="display:flex;gap:10px;margin-bottom:12px;font-size:13px;color:var(--muted);line-height:1.5;">
                <span>{{ $icon }}</span><span>{{ $tip }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
