<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:    #0d0d14;
            --gold:   #d4a843;
            --paper:  #f5f2ed;
            --muted:  #7a7570;
            --border: #e2ddd6;
            --red:    #c0392b;
            --green:  #1e7e52;
            --cream:  #faf8f4;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--ink);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Left Panel ── */
        .auth-left {
            width: 45%;
            min-height: 100vh;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative grid */
        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(212,168,67,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,168,67,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Glow orb */
        .auth-left::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212,168,67,0.12) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .left-brand {
            position: relative;
            z-index: 1;
        }
        .left-brand .logo {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            letter-spacing: -0.3px;
        }
        .left-brand .logo span { color: var(--gold); }

        .left-hero {
            position: relative;
            z-index: 1;
        }
        .left-hero h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(28px, 3vw, 40px);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .left-hero h2 span { color: var(--gold); }
        .left-hero p {
            font-size: 15px;
            color: rgba(255,255,255,0.45);
            line-height: 1.7;
            max-width: 320px;
        }

        /* Stats row */
        .stats-row {
            display: flex;
            gap: 24px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .stat {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--gold);
        }
        .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .left-footer {
            position: relative;
            z-index: 1;
            font-size: 12px;
            color: rgba(255,255,255,0.2);
        }

        /* ── Right Panel ── */
        .auth-right {
            flex: 1;
            background: var(--paper);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
        }

        .auth-card-header {
            margin-bottom: 32px;
        }
        .auth-card-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 6px;
        }
        .auth-card-header p {
            font-size: 14px;
            color: var(--muted);
        }
        .auth-card-header p a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
        }
        .auth-card-header p a:hover { text-decoration: underline; }

        /* Alerts */
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
        .alert-success { background: #e8f5ee; border: 1px solid #b2dfc4; color: var(--green); }
        .alert-error   { background: #fdf0ee; border: 1px solid #f0c0bb; color: var(--red); }

        /* Form */
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

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--ink);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            -webkit-appearance: none;
        }
        @media (max-width: 600px) {
            input[type="text"], input[type="email"], input[type="password"] { font-size: 16px; }
        }
        input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212,168,67,0.12);
        }
        input.invalid { border-color: var(--red); }

        .field-error { font-size: 12px; color: var(--red); margin-top: 5px; }

        /* Password toggle wrapper */
        .input-wrap { position: relative; }
        .input-wrap input { padding-right: 44px; }
        .pwd-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 18px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            min-width: 16px;
            margin-top: 2px;
            accent-color: var(--gold);
            cursor: pointer;
        }
        .checkbox-group label {
            font-size: 13px;
            color: var(--muted);
            text-transform: none;
            letter-spacing: 0;
            font-weight: 400;
            margin-bottom: 0;
            cursor: pointer;
            line-height: 1.5;
        }
        .checkbox-group label a { color: var(--gold); text-decoration: none; }
        .checkbox-group label a:hover { text-decoration: underline; }

        /* Remember + Forgot row */
        .form-row-inline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .form-row-inline label {
            font-size: 13px;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 400;
            color: var(--muted);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
        }
        .form-row-inline a {
            font-size: 13px;
            color: var(--gold);
            text-decoration: none;
        }
        .form-row-inline a:hover { text-decoration: underline; }

        /* Button */
        .btn {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.3px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-primary {
            background: var(--ink);
            color: #fff;
        }
        .btn-primary:hover {
            background: #1e1e30;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(13,13,20,0.2);
        }
        .btn-primary:active { transform: translateY(0); }

        /* Divider */
        .form-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .auth-left { display: none; }
            .auth-right { padding: 32px 20px; align-items: flex-start; padding-top: 48px; }
        }
    </style>
</head>
<body>

    <!-- Left Panel -->
    <div class="auth-left">
        <div class="left-brand">
            <a href="{{ route('login') }}" class="logo">Mail<span>Flow</span></a>
        </div>

        <div class="left-hero">
            <h2>Professional<br>Email <span>Marketing</span><br>Platform</h2>
            <p>Send campaigns, manage contacts, track analytics — all from one powerful dashboard.</p>

            <div class="stats-row">
                <div class="stat">
                    <span class="stat-value">99.9%</span>
                    <span class="stat-label">Deliverability</span>
                </div>
                <div class="stat">
                    <span class="stat-value">∞</span>
                    <span class="stat-label">Templates</span>
                </div>
                <div class="stat">
                    <span class="stat-value">Real-time</span>
                    <span class="stat-label">Analytics</span>
                </div>
            </div>
        </div>

        <div class="left-footer">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">
        <div class="auth-card">

            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">❌ {{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

</body>
</html>
