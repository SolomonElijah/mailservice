<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel SMTP Mailer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:    #0d0d14;
            --paper:  #f5f2ed;
            --gold:   #d4a843;
            --cream:  #faf8f4;
            --muted:  #7a7570;
            --border: #e2ddd6;
            --red:    #c0392b;
            --green:  #1e7e52;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── Header ── */
        header {
            background: var(--ink);
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 3px solid var(--gold);
        }
        header .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: #fff;
            letter-spacing: -0.3px;
        }
        header .logo span { color: var(--gold); }
        header .pill {
            background: rgba(212,168,67,0.15);
            border: 1px solid var(--gold);
            color: var(--gold);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 100px;
        }

        /* ── Layout ── */
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 50px 24px 80px;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 38px;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 8px;
        }
        .page-title span { color: var(--gold); }
        .page-subtitle {
            color: var(--muted);
            font-size: 15px;
            margin-bottom: 40px;
        }

        /* ── Tab Bar ── */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 32px;
            border-bottom: 2px solid var(--border);
        }
        .tab-btn {
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all .2s;
            letter-spacing: 0.2px;
        }
        .tab-btn:hover { color: var(--ink); }
        .tab-btn.active {
            color: var(--ink);
            border-bottom-color: var(--gold);
        }

        /* ── Panels ── */
        .panel { display: none; }
        .panel.active { display: block; }

        /* ── Card ── */
        .card {
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            background: var(--ink);
            padding: 22px 32px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .card-header .icon {
            width: 40px;
            height: 40px;
            background: var(--gold);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .card-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        .card-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin-top: 2px;
        }
        .card-body { padding: 32px; }

        /* ── Form ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-group.full { grid-column: 1 / -1; }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        label .req { color: var(--gold); }
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 13px 16px;
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14.5px;
            color: var(--ink);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        input:focus, textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212,168,67,0.12);
        }
        textarea { resize: vertical; min-height: 140px; line-height: 1.7; }

        .hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        /* ── Button ── */
        .btn {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--ink);
            color: #fff;
        }
        .btn-primary:hover {
            background: #1e1e30;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(13,13,20,0.2);
        }
        .btn-primary:active { transform: translateY(0); }

        /* ── Alerts ── */
        .alert {
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 28px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }
        .alert-success {
            background: #e8f5ee;
            border: 1px solid #b2dfc4;
            color: var(--green);
        }
        .alert-error {
            background: #fdf0ee;
            border: 1px solid #f0c0bb;
            color: var(--red);
        }
        .alert-warning {
            background: #fdf8e8;
            border: 1px solid #f0dfa0;
            color: #8a6500;
        }
        .alert .icon { font-size: 17px; flex-shrink: 0; margin-top: 1px; }

        /* ── Validation errors ── */
        .field-error {
            font-size: 12px;
            color: var(--red);
            margin-top: 5px;
        }
        input.invalid, textarea.invalid {
            border-color: var(--red);
        }

        /* ── Config Note ── */
        .config-box {
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 24px 28px;
            margin-top: 32px;
        }
        .config-box h3 {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .config-box code {
            display: block;
            background: var(--ink);
            color: var(--gold);
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 2;
            font-family: 'Courier New', monospace;
            white-space: pre;
            overflow-x: auto;
        }

        @media (max-width: 640px) {
            header { padding: 16px 20px; }
            .page { padding: 30px 16px 60px; }
            .page-title { font-size: 28px; }
            .form-row { grid-template-columns: 1fr; }
            .card-body { padding: 20px; }
            .tab-btn { padding: 10px 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Laravel <span>Mailer</span></div>
    <div class="pill">SMTP Powered</div>
</header>

<div class="page">

    <h1 class="page-title">Send <span>Emails</span><br>via SMTP</h1>
    <p class="page-subtitle">Single recipient, bulk send, or advanced CC/BCC — all powered by Laravel & SMTP.</p>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span class="icon">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <span class="icon">❌</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">
            <span class="icon">⚠️</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('single', this)">✉️ Single Email</button>
        <button class="tab-btn" onclick="switchTab('multiple', this)">📬 Multiple Emails</button>
        <button class="tab-btn" onclick="switchTab('advanced', this)">⚙️ Advanced (CC/BCC)</button>
        <button class="tab-btn" onclick="switchTab('config', this)">🔧 Configuration</button>
    </div>

    {{-- ══ SINGLE EMAIL ══ --}}
    <div id="tab-single" class="panel active">
        <div class="card">
            <div class="card-header">
                <div class="icon">✉️</div>
                <div>
                    <h2>Send to Single Recipient</h2>
                    <p>Compose and send an email to one address</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.single') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label>Recipient Email <span class="req">*</span></label>
                            <input type="email" name="to" placeholder="user@example.com"
                                value="{{ old('to') }}"
                                class="{{ $errors->has('to') ? 'invalid' : '' }}">
                            @error('to') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label>Recipient Name</label>
                            <input type="text" name="name" placeholder="John Doe"
                                value="{{ old('name') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" placeholder="Your email subject"
                            value="{{ old('subject') }}"
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        <textarea name="body" placeholder="Write your message here..."
                            class="{{ $errors->has('body') ? 'invalid' : '' }}">{{ old('body') }}</textarea>
                        @error('body') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>➤</span> Send Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══ MULTIPLE EMAILS ══ --}}
    <div id="tab-multiple" class="panel">
        <div class="card">
            <div class="card-header">
                <div class="icon">📬</div>
                <div>
                    <h2>Send to Multiple Recipients</h2>
                    <p>Bulk send — each recipient gets an individual email</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.multiple') }}">
                    @csrf
                    <div class="form-group">
                        <label>Recipients <span class="req">*</span></label>
                        <textarea name="recipients" rows="4"
                            placeholder="user1@example.com, user2@example.com&#10;user3@example.com"
                            class="{{ $errors->has('recipients') ? 'invalid' : '' }}">{{ old('recipients') }}</textarea>
                        <p class="hint">Separate addresses with commas, semicolons, or new lines.</p>
                        @error('recipients') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" placeholder="Your email subject"
                            value="{{ old('subject') }}"
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        <textarea name="body" placeholder="Write your message here..."
                            class="{{ $errors->has('body') ? 'invalid' : '' }}">{{ old('body') }}</textarea>
                        @error('body') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>➤</span> Send to All
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══ ADVANCED ══ --}}
    <div id="tab-advanced" class="panel">
        <div class="card">
            <div class="card-header">
                <div class="icon">⚙️</div>
                <div>
                    <h2>Advanced Send — CC & BCC</h2>
                    <p>Full control over recipients with carbon copy support</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email.send.advanced') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label>To <span class="req">*</span></label>
                            <input type="email" name="to" placeholder="primary@example.com"
                                value="{{ old('to') }}"
                                class="{{ $errors->has('to') ? 'invalid' : '' }}">
                            @error('to') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" placeholder="Recipient name"
                                value="{{ old('name') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CC</label>
                            <input type="text" name="cc" placeholder="cc1@example.com, cc2@example.com"
                                value="{{ old('cc') }}">
                            <p class="hint">Separate with commas</p>
                        </div>
                        <div class="form-group">
                            <label>BCC</label>
                            <input type="text" name="bcc" placeholder="bcc1@example.com, bcc2@example.com"
                                value="{{ old('bcc') }}">
                            <p class="hint">Separate with commas</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="req">*</span></label>
                        <input type="text" name="subject" placeholder="Your email subject"
                            value="{{ old('subject') }}"
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Message <span class="req">*</span></label>
                        <textarea name="body" placeholder="Write your message here..."
                            class="{{ $errors->has('body') ? 'invalid' : '' }}">{{ old('body') }}</textarea>
                        @error('body') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>➤</span> Send Advanced Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══ CONFIGURATION ══ --}}
    <div id="tab-config" class="panel">
        <div class="card">
            <div class="card-header">
                <div class="icon">🔧</div>
                <div>
                    <h2>SMTP Configuration Guide</h2>
                    <p>Set up your .env file to connect to your SMTP provider</p>
                </div>
            </div>
            <div class="card-body">

                <div class="config-box">
                    <h3>⚙️ .env — Basic SMTP Setup</h3>
                    <code>MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="Laravel Mailer"</code>
                </div>

                <div class="config-box" style="margin-top:20px">
                    <h3>📦 Popular SMTP Providers</h3>
                    <code>## Gmail (use App Password, not account password)
MAIL_HOST=smtp.gmail.com  |  PORT=587  |  ENCRYPTION=tls

## Outlook / Hotmail
MAIL_HOST=smtp-mail.outlook.com  |  PORT=587  |  ENCRYPTION=tls

## Mailtrap (testing — inbox.mailtrap.io)
MAIL_HOST=sandbox.smtp.mailtrap.io  |  PORT=2525

## SendGrid
MAIL_HOST=smtp.sendgrid.net  |  PORT=587  |  USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key

## Mailgun
MAIL_HOST=smtp.mailgun.org  |  PORT=587</code>
                </div>

                <div class="config-box" style="margin-top:20px">
                    <h3>🚀 Artisan Commands</h3>
                    <code># Install dependencies
composer install

# Copy env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Clear config cache after editing .env
php artisan config:clear

# Start development server
php artisan serve</code>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    function switchTab(id, btn) {
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + id).classList.add('active');
        btn.classList.add('active');
    }

    // Auto-open the tab that has validation errors or flash messages
    document.addEventListener('DOMContentLoaded', () => {
        const url = window.location.href;
        @if($errors->any() || session('success') || session('error') || session('warning'))
            // Keep current tab active via session if needed
        @endif
    });
</script>

</body>
</html>
