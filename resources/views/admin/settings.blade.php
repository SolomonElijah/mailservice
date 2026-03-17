@extends('layouts.app')
@section('title', 'System Settings')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">System Settings ⚙️</h1>
        <p class="page-subtitle">Server info, cache management and maintenance tools</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Admin</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    {{-- System Info --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🖥️</div>
            <div><h3>System Info</h3></div>
        </div>
        <div class="card-body" style="padding:0;">
            @foreach([
                ['PHP Version',     $phpVersion],
                ['Laravel Version', $laravelVersion],
                ['Environment',     app()->environment()],
                ['Debug Mode',      config('app.debug') ? '⚠️ ON' : '✅ OFF'],
                ['Queue Driver',    config('queue.default')],
                ['Mail Driver',     config('mail.default')],
                ['Cache Driver',    config('cache.default')],
                ['Database Size',   $dbSize],
                ['Log File Size',   $logSize . ' MB'],
                ['App URL',         config('app.url')],
            ] as [$label, $val])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:11px 20px;border-bottom:1px solid var(--border);font-size:13.5px;">
                <span style="color:var(--muted);">{{ $label }}</span>
                <span style="font-weight:600;font-family:'Courier New',monospace;font-size:12px;">{{ $val }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Maintenance Actions --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="card">
            <div class="card-header">
                <div class="card-icon">🧹</div>
                <div><h3>Cache Management</h3><p>Clear compiled files and caches</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:16px;line-height:1.6;">
                    Clears application cache, route cache, config cache, and compiled views. Run this after deploying changes.
                </p>
                <form method="POST" action="{{ route('admin.settings.clear-cache') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        🧹 Clear All Caches
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <div><h3>Log Management</h3><p>Laravel application logs</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:16px;line-height:1.6;">
                    Current log size: <strong>{{ $logSize }} MB</strong>.
                    Clears <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:12px;">storage/logs/laravel.log</code>.
                </p>
                <form method="POST" action="{{ route('admin.settings.clear-logs') }}"
                    onsubmit="return confirm('Clear the log file?')">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:center;">
                        🗑 Clear Logs
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-icon">🔗</div>
                <div><h3>Quick Links</h3></div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('analytics.webhook-setup') }}" class="btn btn-secondary" style="justify-content:flex-start;">
                    🔔 Webhook Setup Guide
                </a>
                <a href="{{ route('admin.queues') }}" class="btn btn-secondary" style="justify-content:flex-start;">
                    ⚙️ Queue Monitor
                </a>
                <a href="{{ route('admin.users') }}" class="btn btn-secondary" style="justify-content:flex-start;">
                    👥 User Management
                </a>
                <a href="{{ route('analytics.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">
                    📈 Analytics Dashboard
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
