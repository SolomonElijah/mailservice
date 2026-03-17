<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Georgia', serif;
            background: #f4f1ec;
            color: #2c2c2c;
            line-height: 1.7;
        }
        .wrapper {
            max-width: 620px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: #1a1a2e;
            padding: 36px 40px;
            border-bottom: 4px solid #e8a838;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        .header .badge {
            display: inline-block;
            background: #e8a838;
            color: #1a1a2e;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 2px;
            margin-bottom: 12px;
        }
        .body {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            color: #1a1a2e;
            margin-bottom: 20px;
            font-style: italic;
        }
        .message {
            font-size: 15px;
            color: #444;
            line-height: 1.9;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .divider {
            border: none;
            border-top: 1px solid #e8e0d4;
            margin: 32px 0;
        }
        .footer {
            background: #f9f6f0;
            padding: 24px 40px;
            font-size: 12px;
            color: #999;
            text-align: center;
            border-top: 1px solid #e8e0d4;
        }
        .footer strong { color: #1a1a2e; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="badge">New Message</div>
            <h1>{{ $emailSubject }}</h1>
        </div>

        <div class="body">
            <p class="greeting">Hello, {{ $recipientName }}!</p>

            <hr class="divider">

            <div class="message">{{ $emailBody }}</div>

            <hr class="divider">

            <p style="font-size: 13px; color: #888;">
                This email was sent via <strong style="color:#1a1a2e;">Laravel Mailer</strong>.<br>
                Sent on {{ now()->format('F j, Y \a\t g:i A') }}
            </p>
        </div>

        <div class="footer">
            <strong>{{ env('APP_NAME') }}</strong>
        </div>
    </div>
</body>
</html>
