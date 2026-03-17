<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\ScheduledEmail;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    // ── Dashboard ──────────────────────────────────
    public function dashboard()
    {
        $stats = [
            'users'      => User::count(),
            'emails_sent'=> EmailLog::where('status', 'sent')->count(),
            'campaigns'  => Campaign::count(),
            'contacts'   => Contact::count(),
            'templates'  => EmailTemplate::count(),
            'lists'      => ContactList::count(),
            'scheduled'  => ScheduledEmail::where('status', 'pending')->count(),
            'failed'     => EmailLog::where('status', 'failed')->count(),
        ];

        // Emails per day (last 14 days)
        $activity = EmailLog::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Top senders
        $topSenders = User::withCount(['emailLogs as sent_count' => fn($q) => $q->where('status', 'sent')])
            ->orderByDesc('sent_count')
            ->limit(5)
            ->get();

        // Recent failed jobs
        $failedJobs = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(10)->get();

        // Queue status
        $queueDepth = DB::table('jobs')->count();
        $pendingJobs = DB::table('jobs')->select('queue', DB::raw('count(*) as cnt'))
            ->groupBy('queue')->get();

        // Recent webhook events
        $recentWebhooks = WebhookEvent::orderByDesc('created_at')->limit(8)->get();

        return view('admin.dashboard', compact(
            'stats', 'activity', 'topSenders',
            'failedJobs', 'queueDepth', 'pendingJobs', 'recentWebhooks'
        ));
    }

    // ── User Management ────────────────────────────
    public function users(Request $request)
    {
        $query = User::withCount([
            'emailLogs as sent_count' => fn($q) => $q->where('status', 'sent'),
            'campaigns',
        ]);

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%')
            );
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role'  => 'required|in:user,admin',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->update($request->only('name', 'email', 'role'));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', "User {$user->name} updated.");
    }

    public function deleteUser(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Cannot delete your own account.');
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    public function impersonate(User $user)
    {
        session(['admin_impersonating' => auth()->id()]);
        auth()->login($user);
        return redirect()->route('dashboard')->with('success', "Now viewing as {$user->name}.");
    }

    public function stopImpersonating()
    {
        $adminId = session('admin_impersonating');
        if ($adminId) {
            session()->forget('admin_impersonating');
            auth()->loginUsingId($adminId);
        }
        return redirect()->route('admin.dashboard')->with('success', 'Returned to admin account.');
    }

    // ── Queue Monitor ──────────────────────────────
    public function queues()
    {
        $pending = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as cnt, min(created_at) as oldest'))
            ->groupBy('queue')
            ->get();

        $failed = DB::table('failed_jobs')->orderByDesc('failed_at')->paginate(20);

        $processed = [
            'last_hour' => EmailLog::where('created_at', '>=', now()->subHour())->count(),
            'last_day'  => EmailLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        return view('admin.queues', compact('pending', 'failed', 'processed'));
    }

    public function retryJob(Request $request)
    {
        $uuid = $request->uuid;
        \Artisan::call('queue:retry', ['id' => [$uuid]]);
        return back()->with('success', "Job {$uuid} queued for retry.");
    }

    public function flushFailed()
    {
        \Artisan::call('queue:flush');
        return back()->with('success', 'All failed jobs flushed.');
    }

    // ── System Settings ────────────────────────────
    public function settings()
    {
        $phpVersion     = PHP_VERSION;
        $laravelVersion = app()->version();
        $dbSize         = $this->getDatabaseSize();
        $logSize        = file_exists(storage_path('logs/laravel.log'))
            ? round(filesize(storage_path('logs/laravel.log')) / 1024 / 1024, 2)
            : 0;

        return view('admin.settings', compact(
            'phpVersion', 'laravelVersion', 'dbSize', 'logSize'
        ));
    }

    public function clearCache()
    {
        \Artisan::call('optimize:clear');
        return back()->with('success', 'Cache cleared successfully.');
    }

    public function clearLogs()
    {
        $path = storage_path('logs/laravel.log');
        if (file_exists($path)) {
            file_put_contents($path, '');
        }
        return back()->with('success', 'Log file cleared.');
    }

    // ── Helpers ────────────────────────────────────
    private function getDatabaseSize(): string
    {
        try {
            $db   = config('database.connections.mysql.database');
            $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                                FROM information_schema.tables
                                WHERE table_schema = ?", [$db]);
            return ($size[0]->size ?? 0) . ' MB';
        } catch (\Throwable) {
            return 'N/A';
        }
    }
}
