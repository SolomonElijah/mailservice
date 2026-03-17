<?php

namespace App\Http\Controllers;

use App\Jobs\SendScheduledEmailJob;
use App\Models\ScheduledEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SchedulerController extends Controller
{
    public function index()
    {
        $scheduled = ScheduledEmail::where('user_id', Auth::id())
            ->orderByDesc('send_at')
            ->paginate(20);

        $stats = [
            'pending'    => ScheduledEmail::where('user_id', Auth::id())->where('status', 'pending')->count(),
            'sent'       => ScheduledEmail::where('user_id', Auth::id())->where('status', 'sent')->count(),
            'failed'     => ScheduledEmail::where('user_id', Auth::id())->where('status', 'failed')->count(),
            'processing' => ScheduledEmail::where('user_id', Auth::id())->where('status', 'processing')->count(),
        ];

        return view('scheduler.index', compact('scheduled', 'stats'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_email'  => 'required|email',
            'to_name'   => 'nullable|string|max:100',
            'subject'   => 'required|string|max:255',
            'html_body' => 'required|string',
            'send_at'   => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $scheduled = ScheduledEmail::create([
            'user_id'   => Auth::id(),
            'to_email'  => $request->to_email,
            'to_name'   => $request->to_name,
            'subject'   => $request->subject,
            'html_body' => $request->html_body,
            'plain_body'=> $request->plain_body,
            'send_at'   => $request->send_at,
            'status'    => 'pending',
        ]);

        return back()->with('success', "Email scheduled for {$scheduled->send_at->format('M j, Y g:i A')}!");
    }

    public function cancel(ScheduledEmail $email)
    {
        abort_if($email->user_id !== Auth::id(), 403);
        abort_if($email->status !== 'pending', 422, 'Only pending emails can be cancelled.');

        $email->update(['status' => 'cancelled']);
        return back()->with('success', 'Scheduled email cancelled.');
    }

    public function retry(ScheduledEmail $email)
    {
        abort_if($email->user_id !== Auth::id(), 403);
        abort_if($email->status !== 'failed', 422, 'Only failed emails can be retried.');

        $email->update([
            'status'        => 'processing',
            'error_message' => null,
        ]);

        SendScheduledEmailJob::dispatch($email->id)->onQueue('emails');

        return back()->with('success', 'Email queued for immediate retry.');
    }

    public function destroy(ScheduledEmail $email)
    {
        abort_if($email->user_id !== Auth::id(), 403);
        $email->delete();
        return back()->with('success', 'Scheduled email deleted.');
    }
}
