<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // ── Core stats ──
        $totalSent     = EmailLog::forUser($userId)->count();
        $sentToday     = EmailLog::forUser($userId)->today()->count();
        $sentThisMonth = EmailLog::forUser($userId)->thisMonth()->count();
        $sentThisWeek  = EmailLog::forUser($userId)->thisWeek()->count();

        $totalOpened   = EmailLog::forUser($userId)->whereNotNull('opened_at')->count();
        $totalFailed   = EmailLog::forUser($userId)->where('status', 'failed')->count();
        $totalClicked  = EmailLog::forUser($userId)->whereNotNull('clicked_at')->count();

        $openRate    = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 1) : 0;
        $clickRate   = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 1) : 0;
        $failureRate = $totalSent > 0 ? round(($totalFailed / $totalSent) * 100, 1) : 0;

        // ── Last 7 days chart data ──
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) use ($userId) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'  => $date->format('M d'),
                'sent'  => EmailLog::forUser($userId)->whereDate('created_at', $date)->count(),
                'opened'=> EmailLog::forUser($userId)->whereDate('created_at', $date)->whereNotNull('opened_at')->count(),
            ];
        });

        // ── Last 30 days chart data ──
        $last30Days = collect(range(29, 0))->map(function ($daysAgo) use ($userId) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date' => $date->format('M d'),
                'sent' => EmailLog::forUser($userId)->whereDate('created_at', $date)->count(),
            ];
        });

        // ── Email type breakdown ──
        $typeBreakdown = EmailLog::forUser($userId)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // ── Recent activity (last 10) ──
        $recentLogs = EmailLog::forUser($userId)
            ->latest()
            ->limit(10)
            ->get();

        // ── Top recipients ──
        $topRecipients = EmailLog::forUser($userId)
            ->select('recipient_email', DB::raw('count(*) as count'))
            ->groupBy('recipient_email')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // ── Monthly comparison ──
        $thisMonthCount = EmailLog::forUser($userId)->thisMonth()->count();
        $lastMonthCount = EmailLog::forUser($userId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $monthlyGrowth = $lastMonthCount > 0
            ? round((($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 1)
            : ($thisMonthCount > 0 ? 100 : 0);

        return view('dashboard.index', compact(
            'totalSent',
            'sentToday',
            'sentThisMonth',
            'sentThisWeek',
            'totalOpened',
            'totalFailed',
            'totalClicked',
            'openRate',
            'clickRate',
            'failureRate',
            'last7Days',
            'last30Days',
            'typeBreakdown',
            'recentLogs',
            'topRecipients',
            'thisMonthCount',
            'lastMonthCount',
            'monthlyGrowth'
        ));
    }

    public function logs(Request $request)
    {
        $userId = Auth::id();

        $query = EmailLog::forUser($userId)->latest();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('recipient_email', 'like', '%' . $request->search . '%')
                  ->orWhere('subject', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('dashboard.logs', compact('logs'));
    }
}
