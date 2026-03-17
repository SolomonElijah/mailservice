{{-- Replace the <nav class="sidebar-nav"> section in layouts/app.blade.php with this --}}

<nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="{{ route('dashboard') }}"
       class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="nav-icon">📊</span> Dashboard
    </a>

    <a href="{{ route('email.index') }}"
       class="nav-item {{ request()->routeIs('email.*') ? 'active' : '' }}">
        <span class="nav-icon">✉️</span> Send Email
    </a>

    <a href="{{ route('logs.index') }}"
       class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
        <span class="nav-icon">📋</span> Email Logs
    </a>

    <div class="nav-section-label">Campaigns</div>

    <a href="{{ route('campaigns.index') }}"
       class="nav-item {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
        <span class="nav-icon">📣</span> Campaigns
    </a>

    <a href="{{ route('templates.index') }}"
       class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
        <span class="nav-icon">🎨</span> Templates
    </a>

    <a href="{{ route('contacts.index') }}"
       class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}" style="opacity:0.5;">
        <span class="nav-icon">👥</span> Contacts
        <span class="nav-badge">S4</span>
    </a>

    <div class="nav-section-label">Account</div>

    <a href="{{ route('profile') }}"
       class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
        <span class="nav-icon">👤</span> Profile
    </a>

    @if(Auth::user()->isAdmin())
    <a href="#" class="nav-item">
        <span class="nav-icon">⚙️</span> Settings
    </a>
    @endif
</nav>
