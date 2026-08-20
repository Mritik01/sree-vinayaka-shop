<?php

namespace App\Services;

use App\Models\SiteVisit;
use App\Models\UserActivity;
use App\Support\DeviceDetector;
use App\Support\IpGeolocationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// event-name convention: plain names (page_view, add_to_cart, login, ...) for the original
// lifecycle events; a 'click:' prefix (e.g. click:product_card, click:checkout_start) for the
// short, deliberately-curated list of meaningful UI interactions — this is NOT a generic
// "track every click" layer, see ActivityTrackingController::click()'s whitelist; and
// 'session_end' for the visibilitychange/pagehide beacon recording where a visitor was when they
// left. All rows (registered users and guests alike) are deleted after 3 days — see
// PruneAnalyticsData — so nothing here is pruned by row-count anymore, only by age.
class ActivityLogger
{
    public static function log(string $event, ?string $label = null, array $extra = []): void
    {
        // an admin impersonating this customer must never pollute their real activity feed
        // or "last seen / online" indicator (see ImpersonationService)
        if (ImpersonationService::active()) {
            return;
        }

        $request = request();
        $ua = (string) $request->userAgent();
        $device = DeviceDetector::parse($ua);
        $userId = Auth::id();
        $sessionId = $request->session()->getId();

        try {
            // server-side backstop against duplicate session_end rows — the Laravel session_id is
            // shared across every tab of the same browser (unlike the client's sessionStorage
            // debounce in app.js, which is per-tab), so a visitor with multiple tabs open can
            // produce several genuinely independent beacons for the same session in quick
            // succession. This has already needed two client-only fix attempts; enforcing it here
            // too means duplicates can't get through regardless of the cause.
            if ($event === 'session_end' && UserActivity::where('session_id', $sessionId)
                    ->where('event', 'session_end')
                    ->where('created_at', '>=', now()->subSeconds(60))
                    ->exists()) {
                return;
            }

            UserActivity::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'event' => $event,
                'label' => $label,
                'path' => '/'.ltrim($request->path(), '/'),
                'url' => $request->fullUrl(),
                'product_id' => $extra['product_id'] ?? null,
                'device_type' => $device['device_type'],
                'browser' => $device['browser'],
                'platform' => $device['platform'],
                'ip_address' => $request->ip(),
                'user_agent' => $ua,
                'referrer' => $request->headers->get('referer'),
                'created_at' => now(),
            ]);

            static::trimToCap();
            static::recordVisit($sessionId, $userId, $device, $request, $event);
        } catch (\Throwable $e) {
            // analytics must never break the customer-facing request
            Log::debug('ActivityLogger failed: '.$e->getMessage());
        }
    }

    // the Activity Stream (admin.analytics.index) is meant to be a lightweight, recent-activity
    // feed, not a full historical log — the 3-day age-based prune (see PruneAnalyticsData) still
    // runs daily, but a single noisy burst (e.g. the session_end duplication this cap was added
    // right after) could otherwise flood the table for hours before that scheduled job catches
    // it. Enforced on every write instead: keeps the table at ~200 rows at all times, so this
    // never depends on a schedule catching up.
    private const MAX_ROWS = 200;

    private static function trimToCap(): void
    {
        $excess = UserActivity::count() - self::MAX_ROWS;
        if ($excess <= 0) {
            return;
        }

        $idsToDelete = UserActivity::oldest('created_at')->limit($excess)->pluck('id');
        UserActivity::whereIn('id', $idsToDelete)->delete();
    }

    // one row per session (guest or logged-in), updated in place — this is how "how many people
    // are visiting the site" is answered without storing an ever-growing per-page event log
    private static function recordVisit(string $sessionId, ?int $userId, array $device, $request, string $event): void
    {
        $visit = SiteVisit::where('session_id', $sessionId)->first();

        if (!$visit) {
            // once per new session only — never on every page view — see IpGeolocationService
            $location = app(IpGeolocationService::class)->lookup($request->ip());

            SiteVisit::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'device_type' => $device['device_type'],
                'browser' => $device['browser'],
                'platform' => $device['platform'],
                'ip_address' => $request->ip(),
                'city' => $location['city'] ?? null,
                'country' => $location['country'] ?? null,
                'entry_path' => '/'.ltrim($request->path(), '/'),
                'page_views' => $event === 'page_view' ? 1 : 0,
                'first_seen' => now(),
                'last_seen' => now(),
            ]);

            return;
        }

        $visit->user_id = $userId ?? $visit->user_id;
        $visit->device_type = $device['device_type'];
        $visit->browser = $device['browser'];
        $visit->platform = $device['platform'];
        $visit->last_seen = now();
        if ($event === 'page_view') {
            $visit->page_views++;
        }
        $visit->save();
    }
}
