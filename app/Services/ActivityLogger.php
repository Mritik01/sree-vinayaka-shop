<?php

namespace App\Services;

use App\Models\SiteVisit;
use App\Models\UserActivity;
use App\Support\DeviceDetector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    // keeps the detailed per-action log bounded — see recordVisit() below, which has its
    // own cap (KEEP_VISITS) for the one-row-per-session traffic-counting table
    private const KEEP_PER_USER = 15;

    // Visitors admin page only ever needs to show recent traffic — capping this keeps the
    // table from growing forever as guest sessions pile up
    private const KEEP_VISITS = 100;

    public static function log(string $event, ?string $label = null, array $extra = []): void
    {
        $request = request();
        $ua = (string) $request->userAgent();
        $device = DeviceDetector::parse($ua);
        $userId = Auth::id();
        $sessionId = $request->session()->getId();

        try {
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

            static::pruneOldActivity($userId);
            static::recordVisit($sessionId, $userId, $device, $request, $event);
        } catch (\Throwable $e) {
            // analytics must never break the customer-facing request
            Log::debug('ActivityLogger failed: '.$e->getMessage());
        }
    }

    // keeps only the most recent N raw actions per logged-in user so this table can't grow unbounded
    private static function pruneOldActivity(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $idsToKeep = UserActivity::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::KEEP_PER_USER)
            ->pluck('id');

        UserActivity::where('user_id', $userId)->whereNotIn('id', $idsToKeep)->delete();
    }

    // keeps only the most recent KEEP_VISITS sessions across the whole site — called only when a
    // brand-new session row is inserted (existing sessions are updated in place, so the total row
    // count never grows on those), so the Visitors admin page stays fast and the table stays bounded
    private static function pruneOldVisits(): void
    {
        $idsToKeep = SiteVisit::orderByDesc('first_seen')
            ->orderByDesc('id')
            ->limit(self::KEEP_VISITS)
            ->pluck('id');

        SiteVisit::whereNotIn('id', $idsToKeep)->delete();
    }

    // one row per session (guest or logged-in), updated in place — this is how "how many people
    // are visiting the site" is answered without storing an ever-growing per-page event log
    private static function recordVisit(string $sessionId, ?int $userId, array $device, $request, string $event): void
    {
        $visit = SiteVisit::where('session_id', $sessionId)->first();

        if (!$visit) {
            SiteVisit::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'device_type' => $device['device_type'],
                'browser' => $device['browser'],
                'platform' => $device['platform'],
                'ip_address' => $request->ip(),
                'entry_path' => '/'.ltrim($request->path(), '/'),
                'page_views' => $event === 'page_view' ? 1 : 0,
                'first_seen' => now(),
                'last_seen' => now(),
            ]);

            static::pruneOldVisits();

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
