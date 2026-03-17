<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unsubscribe</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f0ede8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:16px;padding:48px 40px;max-width:480px;width:100%;text-align:center;box-shadow:0 4px 32px rgba(0,0,0,.08)}
.icon{font-size:56px;margin-bottom:20px}
h1{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#0d0d14;margin-bottom:10px}
p{font-size:15px;color:#666;line-height:1.7;margin-bottom:20px}
.email{background:#f0ede8;padding:10px 16px;border-radius:8px;font-family:'Courier New',monospace;font-size:13px;color:#0d0d14;margin-bottom:24px;word-break:break-all}
.btn{display:inline-block;background:#0d0d14;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:14px}
.muted{font-size:13px;color:#aaa;margin-top:20px}
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&display=swap');
</style>
</head>
<body>
<div class="card">
    @if($status === 'success')
        <div class="icon">✅</div>
        <h1>You've been unsubscribed</h1>
        <p>The following email address has been removed from all mailing lists and will no longer receive emails.</p>
        <div class="email">{{ $email }}</div>
        <p style="font-size:13px;color:#aaa;">Changed your mind? Contact the sender to re-subscribe.</p>

    @elseif($status === 'notfound')
        <div class="icon">🤔</div>
        <h1>Email not found</h1>
        <p>We couldn't find this email address in our system. It may have already been removed.</p>
        @if(isset($email))
        <div class="email">{{ $email }}</div>
        @endif

    @else
        <div class="icon">⚠️</div>
        <h1>Invalid link</h1>
        <p>This unsubscribe link is invalid or has expired. Please contact the sender directly to be removed from their list.</p>
    @endif

    <div class="muted">Powered by FTC Mailer</div>
</div>
</body>
</html>
