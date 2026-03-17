<?php
/*
|----------------------------------------------------------------------
| PATCH: app/Http/Controllers/EmailController.php
|
| Add tracking token injection to your existing send methods.
| Follow the instructions below for each method.
|----------------------------------------------------------------------
*/

// ── 1. Add these use statements at the top of EmailController.php ──
use App\Http\Controllers\TrackingController;

// ── 2. In sendSingle(), replace the EmailLog::create() block with: ──

    $token = TrackingController::generateToken();

    // Inject tracking pixel + link wrapping
    $trackedHtml = TrackingController::injectTracking($html, $token);

    // ... (send email with $trackedHtml instead of $html) ...

    $log = EmailLog::create([
        'user_id'         => Auth::id(),
        'recipient_email' => $validated['to_email'],
        'recipient_name'  => $validated['to_name'] ?? null,
        'subject'         => $validated['subject'],
        'type'            => 'single',
        'status'          => 'sent',
        'tracking_token'  => $token,
        // store Resend message_id if available from response
    ]);

// ── 3. Same pattern for sendMultiple() — inside the foreach loop: ──

    $token = TrackingController::generateToken();
    $trackedHtml = TrackingController::injectTracking($html, $token);
    // ... send with $trackedHtml ...
    EmailLog::create([
        // ... existing fields ...
        'tracking_token' => $token,
    ]);

// ── 4. Same pattern for sendAdvanced() ──
    $token = TrackingController::generateToken();
    $trackedHtml = TrackingController::injectTracking($request->html_body, $token);
    // ... send with $trackedHtml ...
    EmailLog::create([
        // ... existing fields ...
        'tracking_token' => $token,
    ]);
