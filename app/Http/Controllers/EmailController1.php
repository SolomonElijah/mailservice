<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Mail\UserEmail;
use App\Models\EmailLog;
use App\Http\Controllers\TrackingController;

class EmailController extends Controller
{
    public function index()
    {
        return view('emails.compose');
    }

    public function sendSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to'      => 'required|email',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'name'    => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $token = TrackingController::generateToken();
            $trackedBody = TrackingController::injectTracking($request->body, $token);

            Mail::to($request->to, $request->name ?? null)
                ->send(new UserEmail(
                    $request->subject,
                    $trackedBody,
                    $request->name ?? 'User'
                ));

            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => $request->to,
                'recipient_name'  => $request->name,
                'subject'         => $request->subject,
                'type'            => 'single',
                'status'          => 'sent',
                'tracking_token'  => $token,
            ]);

            return back()->with('success', "Email successfully sent to {$request->to}!");
        } catch (\Exception $e) {
            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => $request->to,
                'recipient_name'  => $request->name,
                'subject'         => $request->subject,
                'type'            => 'single',
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send email: ' . $e->getMessage())->withInput();
        }
    }

    public function sendMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|string',
            'subject'    => 'required|string|max:255',
            'body'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $rawEmails = preg_split('/[\s,;]+/', trim($request->recipients));
        $emails    = array_filter(array_map('trim', $rawEmails));

        $invalidEmails = [];
        $validEmails   = [];

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            } else {
                $invalidEmails[] = $email;
            }
        }

        if (empty($validEmails)) {
            return back()->with('error', 'No valid email addresses found.')->withInput();
        }

        $sent   = [];
        $failed = [];

        foreach ($validEmails as $index => $email) {
            try {
                if ($index > 0 && $index % 2 === 0) {
                    usleep(1100000);
                }

                $token = TrackingController::generateToken();
                $trackedBody = TrackingController::injectTracking($request->body, $token);

                Mail::to($email)->send(new UserEmail(
                    $request->subject,
                    $trackedBody,
                    'User'
                ));

                $sent[] = $email;

                EmailLog::create([
                    'user_id'         => Auth::id(),
                    'recipient_email' => $email,
                    'subject'         => $request->subject,
                    'type'            => 'multiple',
                    'status'          => 'sent',
                    'tracking_token'  => $token,
                ]);
            } catch (\Exception $e) {
                $failed[] = $email . ' (' . $e->getMessage() . ')';

                EmailLog::create([
                    'user_id'         => Auth::id(),
                    'recipient_email' => $email,
                    'subject'         => $request->subject,
                    'type'            => 'multiple',
                    'status'          => 'failed',
                    'error_message'   => $e->getMessage(),
                ]);
            }
        }

        $message = count($sent) . ' email(s) sent successfully.';

        if (!empty($invalidEmails)) {
            $message .= ' Invalid addresses skipped: ' . implode(', ', $invalidEmails) . '.';
        }

        if (!empty($failed)) {
            $message .= ' Failed: ' . implode(', ', $failed);
            return back()->with('warning', $message)->withInput();
        }

        return back()->with('success', $message);
    }

    public function sendAdvanced(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to'      => 'required|email',
            'cc'      => 'nullable|string',
            'bcc'     => 'nullable|string',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'name'    => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $token = TrackingController::generateToken();
            $trackedBody = TrackingController::injectTracking($request->body, $token);

            $mailable = new UserEmail(
                $request->subject,
                $trackedBody,
                $request->name ?? 'User'
            );

            $mailer = Mail::to($request->to, $request->name ?? null);

            if ($request->filled('cc')) {
                $ccEmails = array_filter(array_map('trim', preg_split('/[\s,;]+/', $request->cc)));
                if (!empty($ccEmails)) {
                    $mailer->cc($ccEmails);
                }
            }

            if ($request->filled('bcc')) {
                $bccEmails = array_filter(array_map('trim', preg_split('/[\s,;]+/', $request->bcc)));
                if (!empty($bccEmails)) {
                    $mailer->bcc($bccEmails);
                }
            }

            $mailer->send($mailable);

            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => $request->to,
                'recipient_name'  => $request->name,
                'subject'         => $request->subject,
                'type'            => 'advanced',
                'status'          => 'sent',
                'tracking_token'  => $token,
            ]);

            return back()->with('success', "Advanced email sent successfully to {$request->to}!");
        } catch (\Exception $e) {
            EmailLog::create([
                'user_id'         => Auth::id(),
                'recipient_email' => $request->to,
                'recipient_name'  => $request->name,
                'subject'         => $request->subject,
                'type'            => 'advanced',
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send email: ' . $e->getMessage())->withInput();
        }
    }
}