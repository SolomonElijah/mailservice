<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --ink:       #0d0d14;
            --ink-light: #1a1a2e;
            --gold:      #d4a843;
            --gold-dim:  rgba(212,168,67,0.12);
            --paper:     #f5f2ed;
            --cream:     #faf8f4;
            --white:     #ffffff;
            --muted:     #7a7570;
            --border:    #e2ddd6;
            --red:       #c0392b;
            --red-bg:    #fdf0ee;
            --green:     #1e7e52;
            --green-bg:  #e8f5ee;
            --blue:      #2563eb;
            --blue-bg:   #eff6ff;
            --sidebar-w: 240px;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* ════════════════════════════════
           SIDEBAR
        ════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 200;
            transition: transform .3s ease;
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-brand a {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-brand a span { color: var(--gold); }
        .sidebar-brand .version {
            font-size: 10px;
            color: rgba(255,255,255,0.25);
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.2);
            padding: 14px 8px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all .15s;
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }
        .nav-item.active {
            background: var(--gold-dim);
            color: var(--gold);
            font-weight: 600;
        }
        .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .nav-badge {
            margin-left: auto;
            background: var(--gold);
            color: var(--ink);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 100px;
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background .15s;
            text-decoration: none;
        }
        .sidebar-user:hover { background: rgba(255,255,255,0.06); }
        .avatar {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 8px;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
        }
        .sidebar-user-info { overflow: hidden; }
        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
        }

        /* ════════════════════════════════
           MAIN CONTENT
        ════════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top bar */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--ink);
            padding: 4px;
        }
        .breadcrumb {
            font-size: 13px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .breadcrumb strong { color: var(--ink); font-weight: 600; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar-btn {
            background: none;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 7px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink);
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .15s;
            white-space: nowrap;
        }
        .topbar-btn:hover { border-color: var(--gold); color: var(--gold); }
        .topbar-btn.primary {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }
        .topbar-btn.primary:hover { background: #1e1e30; border-color: #1e1e30; color: #fff; }

        /* Page content */
        .content {
            padding: 32px;
            flex: 1;
        }

        /* ── Page Header ── */
        .page-header {
            margin-bottom: 28px;
        }
        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 4px;
        }
        .page-subtitle {
            font-size: 14px;
            color: var(--muted);
        }

        /* ── Cards ── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            background: var(--gold-dim);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .card-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }
        .card-header p {
            font-size: 12px;
            color: var(--muted);
            margin-top: 1px;
        }
        .card-header-action { margin-left: auto; }
        .card-body { padding: 24px; }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.gold::after   { background: var(--gold); }
        .stat-card.green::after  { background: var(--green); }
        .stat-card.red::after    { background: var(--red); }
        .stat-card.blue::after   { background: var(--blue); }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }
        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-meta {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-meta .up   { color: var(--green); }
        .stat-meta .down { color: var(--red); }
        .stat-icon-bg {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 36px;
            opacity: 0.07;
        }

        /* ── Charts Grid ── */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        thead th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--muted);
            padding: 10px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .1s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--cream); }
        tbody td {
            padding: 12px 16px;
            color: var(--ink);
            vertical-align: middle;
        }
        .td-muted { color: var(--muted); font-size: 12px; }

        /* ── Status Badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-sent    { background: var(--green-bg); color: var(--green); }
        .badge-failed  { background: var(--red-bg);   color: var(--red); }
        .badge-opened  { background: var(--blue-bg);  color: var(--blue); }
        .badge-clicked { background: #fdf8e8; color: #8a6500; }
        .badge-bounced { background: #f3f4f6; color: #6b7280; }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }
        .alert-success { background: var(--green-bg); border: 1px solid #b2dfc4; color: var(--green); }
        .alert-error   { background: var(--red-bg);   border: 1px solid #f0c0bb; color: var(--red); }
        .alert-warning { background: #fdf8e8; border: 1px solid #f0dfa0; color: #8a6500; }

        /* ── Forms (shared) ── */
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }
        label .req { color: var(--gold); }
        input[type="text"], input[type="email"], input[type="password"],
        input[type="date"], select, textarea {
            width: 100%;
            padding: 11px 14px;
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--ink);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            -webkit-appearance: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212,168,67,0.1);
        }
        input.invalid, select.invalid, textarea.invalid { border-color: var(--red); }
        textarea { resize: vertical; min-height: 120px; line-height: 1.7; }
        .field-error { font-size: 12px; color: var(--red); margin-top: 5px; }
        .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }

        /* ── Buttons ── */
        .btn {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 13.5px;
            letter-spacing: 0.3px;
            padding: 11px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-primary { background: var(--ink); color: #fff; }
        .btn-primary:hover { background: #1e1e30; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(13,13,20,0.18); }
        .btn-secondary { background: var(--white); color: var(--ink); border: 1.5px solid var(--border); }
        .btn-secondary:hover { border-color: var(--gold); color: var(--gold); }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-danger:hover { background: #a93226; }
        .btn-sm { padding: 7px 14px; font-size: 12px; }
        .btn:active { transform: translateY(0); }





        
        /* ── Overlay for mobile sidebar ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 199;
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main { margin-left: 0; }
            .menu-toggle { display: flex; }
            .content { padding: 20px 16px; }
            .topbar { padding: 0 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-value { font-size: 26px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar Overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}">
            📧 Mail<span>Flow</span>
        </a>
        <div class="version">Email Marketing Platform</div>
    </div>


<nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="{{ route('email.index') }}" class="nav-item {{ request()->routeIs('email.*') ? 'active' : '' }}">
        <span class="nav-icon">✉️</span> Send Email
    </a>
    <a href="{{ route('scheduler.index') }}" class="nav-item {{ request()->routeIs('scheduler.*') ? 'active' : '' }}">
        <span class="nav-icon">⏰</span> Scheduler
    </a>
    <a href="{{ route('logs.index') }}" class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
        <span class="nav-icon">📋</span> Email Logs
    </a>
    <a href="{{ route('analytics.index') }}" class="nav-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
        <span class="nav-icon">📈</span> Analytics
    </a>

    <div class="nav-section-label">Campaigns</div>

    <a href="{{ route('campaigns.index') }}" class="nav-item {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
        <span class="nav-icon">📣</span> Campaigns
    </a>
    <a href="{{ route('templates.index') }}" class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
        <span class="nav-icon">🎨</span> Templates
    </a>
    <a href="{{ route('contacts.index') }}" class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
        <span class="nav-icon">👥</span> Contacts
    </a>

    <div class="nav-section-label">Settings</div>

    <a href="{{ route('providers.index') }}" class="nav-item {{ request()->routeIs('providers.*') ? 'active' : '' }}">
        <span class="nav-icon">📡</span> Providers
    </a>
    <a href="{{ route('profile') }}" class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
        <span class="nav-icon">👤</span> Profile
    </a>

    @if(Auth::user()->isAdmin())
    <div class="nav-section-label">Admin</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <span class="nav-icon">⚙️</span> Admin Panel
    </a>
    <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
        <span class="nav-icon">👥</span> Users
    </a>
    <a href="{{ route('admin.queues') }}" class="nav-item {{ request()->routeIs('admin.queues') ? 'active' : '' }}">
        <span class="nav-icon">🔄</span> Queue Monitor
    </a>
    <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
        <span class="nav-icon">🛠️</span> Settings
    </a>
    @endif
</nav>



    <div class="sidebar-footer">
        <a href="{{ route('profile') }}" class="sidebar-user">
            <div class="avatar">{{ Auth::user()->initials }}</div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ Auth::user()->name }}</div>
                <div class="sidebar-user-role">{{ ucfirst(Auth::user()->role) }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:4px;">
            @csrf
            <button type="submit" class="nav-item" style="width:100%;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.4);font-size:13px;font-family:inherit;">
                <span class="nav-icon">🚪</span> Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<div class="main">

    {{-- Top Bar --}}
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <div class="breadcrumb">
                <span>{{ config('app.name') }}</span>
                <span>›</span>
                <strong>@yield('title', 'Dashboard')</strong>
            </div>
        </div>
        <div class="topbar-right">
            <a href="{{ route('email.index') }}" class="topbar-btn primary">
                ✉️ Compose
            </a>
        </div>
    </header>

    {{-- Content --}}
    <main class="content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">⚠️ {{ session('warning') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
</script>

@stack('scripts')
</body>
</html>
