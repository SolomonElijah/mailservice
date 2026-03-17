<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailClick;
use App\Models\EmailLog;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    // ═══════════════════════════════════════════
    // OPEN TRACKING — 1×1 transparent GIF pixel
    // GET /track/open/{token}
    // ═══════════════════════════════════════════
    public function open(string $token): Response
    {
        $log = EmailLog::where('tracking_token', $token)->first();

        if ($log && !$log->opened_at) {
            $log->update([
                'status'    => 'opened',
                'opened_at' => now(),
            ]);

            // Increment campaign open count
            if ($log->campaign_name) {
                Campaign::whereRaw('name = ? AND user_id = ?', [$log->campaign_name, $log->user_id])
                    ->increment('opened_count');
            }
        }

        // 1×1 transparent GIF — no caching
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
        ]);
    }

    // ═══════════════════════════════════════════
    // CLICK TRACKING — wrap & redirect
    // GET /track/click/{token}?url=...
    // ═══════════════════════════════════════════
    public function click(Request $request, string $token)
    {
        $originalUrl = $request->query('url', '/');
        $log = EmailLog::where('tracking_token', $token)->first();

        EmailClick::create([
            'tracking_token' => $token,
            'email_log_id'   => $log?->id,
            'campaign_id'    => null, // populated via log if needed
            'original_url'   => $originalUrl,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        if ($log && !$log->clicked_at) {
            $log->update([
                'status'     => 'clicked',
                'clicked_at' => now(),
            ]);

            if ($log->campaign_name) {
                Campaign::whereRaw('name = ? AND user_id = ?', [$log->campaign_name, $log->user_id])
                    ->increment('clicked_count');
            }
        }

        return redirect()->away($originalUrl);
    }

    // ═══════════════════════════════════════════
    // ANALYTICS DASHBOARD
    // GET /analytics
    // ═══════════════════════════════════════════
    public function index(Request $request)
    {
        $userId = Auth::id();
        $range  = $request->get('range', '30');  // days

        $since = now()->subDays((int) $range);

        // ── Overview stats ──
        $totalSent    = EmailLog::where('user_id', $userId)->where('created_at', '>=', $since)->count();
        $totalOpened  = EmailLog::where('user_id', $userId)->where('created_at', '>=', $since)->whereNotNull('opened_at')->count();
        $totalClicked = EmailLog::where('user_id', $userId)->where('created_at', '>=', $since)->whereNotNull('clicked_at')->count();
        $totalFailed  = EmailLog::where('user_id', $userId)->where('created_at', '>=', $since)->where('status', 'failed')->count();

        $openRate  = $totalSent  > 0 ? round(($totalOpened  / $totalSent) * 100, 1) : 0;
        $clickRate = $totalSent  > 0 ? round(($totalClicked / $totalSent) * 100, 1) : 0;
        $failRate  = $totalSent  > 0 ? round(($totalFailed  / $totalSent) * 100, 1) : 0;
        $ctr       = $totalOpened > 0 ? round(($totalClicked / $totalOpened) * 100, 1) : 0;

        // ── Daily chart data ──
        $days = (int) $range;
        $chartLabels = [];
        $sentData    = [];
        $openedData  = [];
        $clickedData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('M j');

            $dayLogs = EmailLog::where('user_id', $userId)
                ->whereDate('created_at', $date);

            $sentData[]    = (clone $dayLogs)->count();
            $openedData[]  = (clone $dayLogs)->whereNotNull('opened_at')->count();
            $clickedData[] = (clone $dayLogs)->whereNotNull('clicked_at')->count();
        }

        // ── Campaign performance ──
        $campaigns = Campaign::where('user_id', $userId)
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->limit(10)
            ->get();

        // ── Top clicked links ──
        $topLinks = EmailClick::whereHas('emailLog', fn($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $since)
            ->select('original_url', DB::raw('COUNT(*) as clicks'))
            ->groupBy('original_url')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        // ── Recent opens ──
        $recentOpens = EmailLog::where('user_id', $userId)
            ->whereNotNull('opened_at')
            ->orderByDesc('opened_at')
            ->limit(8)
            ->get();

        // ── Webhook events (last 20) ──
        $webhookEvents = WebhookEvent::orderByDesc('created_at')->limit(20)->get();

        return view('analytics.index', compact(
            'range',
            'totalSent', 'totalOpened', 'totalClicked', 'totalFailed',
            'openRate', 'clickRate', 'failRate', 'ctr',
            'chartLabels', 'sentData', 'openedData', 'clickedData',
            'campaigns', 'topLinks', 'recentOpens', 'webhookEvents'
        ));
    }

    // ═══════════════════════════════════════════
    // Helper: inject tracking into HTML email
    // Called by EmailController / CampaignController
    // ═══════════════════════════════════════════
    public static function injectTracking(string $html, string $token, ?int $campaignId = null): string
    {
        $baseUrl = config('app.url');

        // 1. Wrap all <a href="..."> links (skip mailto: and unsubscribe links)
        $html = preg_replace_callback(
            '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            function ($matches) use ($baseUrl, $token) {
                $url = $matches[0];
                $href = $matches[1];

                // Skip mailto, tel, unsubscribe, anchors
                if (str_starts_with($href, 'mailto:') ||
                    str_starts_with($href, 'tel:') ||
                    str_starts_with($href, '#') ||
                    str_contains($href, 'unsubscribe')) {
                    return $url;
                }

                $tracked = $baseUrl . '/track/click/' . $token . '?url=' . urlencode($href);
                return str_replace($href, $tracked, $url);
            },
            $html
        );

        // 2. Inject open pixel before </body>
        $pixel = '<img src="' . $baseUrl . '/track/open/' . $token . '" '
               . 'width="1" height="1" style="display:block;width:1px;height:1px;opacity:0;" '
               . 'alt="" />';

        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $pixel . '</body>', $html);
        } else {
            $html .= $pixel;
        }

        return $html;
    }

    // ═══════════════════════════════════════════
    // Helper: generate a unique tracking token
    // ═══════════════════════════════════════════
    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (EmailLog::where('tracking_token', $token)->exists());

        return $token;
    }
}
