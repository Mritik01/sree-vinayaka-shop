<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SiteVisit;
use App\Models\UserActivity;
use Illuminate\Http\Request;

// SuperAdmin-only (see admin.super route middleware) — the deeper companion to the all-admin
// Visitors page (VisitorController): a raw event-by-event stream (page views, meaningful clicks,
// cart adds, session-end) for registered customers, plus location/device aggregates, all scoped
// to the last 3 days since that's all PruneAnalyticsData leaves on disk. Deliberately registered-
// customers-only (guest/anonymous activity is excluded here by request) — drill-down reuses the
// existing per-customer "User Behaviour" tab (CustomerController) rather than duplicating it here.
class AnalyticsController extends Controller
{
    use PaginatesAdminLists;

    private const RETENTION_DAYS = 3;

    public function index(Request $request)
    {
        $since = now()->subDays(self::RETENTION_DAYS);
        $search = trim((string) $request->get('q', ''));

        // one row per tracked action, with that session's device/location tacked on via a join
        // (UserActivity itself has no location column — only SiteVisit resolves IP -> city/country,
        // once per session, see IpGeolocationService) — cheaper than an extra query per row.
        // whereNotNull('user_id') excludes guest/anonymous activity from this page entirely.
        $activityQuery = UserActivity::query()
            ->leftJoin('site_visits', 'site_visits.session_id', '=', 'user_activities.session_id')
            ->where('user_activities.created_at', '>=', $since)
            ->whereNotNull('user_activities.user_id')
            ->with(['user:id,name,phone', 'product:id,name'])
            ->select('user_activities.*', 'site_visits.city', 'site_visits.country')
            ->orderByDesc('user_activities.created_at');

        if ($search !== '') {
            $activityQuery->where(function ($q) use ($search) {
                $q->where('user_activities.event', 'like', "%{$search}%")
                    ->orWhere('user_activities.label', 'like', "%{$search}%")
                    ->orWhere('user_activities.path', 'like', "%{$search}%")
                    ->orWhere('user_activities.ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $activities = $activityQuery->paginate($this->perPage($request, 25))->withQueryString();

        // an AJAX live-search request only ever needs the stream partial — skip the
        // stats/chart queries entirely so keystrokes stay fast (same idiom as VisitorController)
        if ($request->ajax()) {
            return view('admin.analytics._stream', ['activities' => $activities, 'search' => $search]);
        }

        $topPages = UserActivity::where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->whereNotNull('label')
            ->selectRaw('label, COUNT(*) as c')
            ->groupBy('label')
            ->orderByDesc('c')
            ->take(10)
            ->pluck('c', 'label');

        $topProductCounts = UserActivity::where('event', 'product_view')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, COUNT(*) as c')
            ->groupBy('product_id')
            ->orderByDesc('c')
            ->take(10)
            ->pluck('c', 'product_id');
        $productNames = Product::whereIn('id', $topProductCounts->keys())->pluck('name', 'id');
        $topProducts = $topProductCounts->mapWithKeys(fn ($count, $productId) => [
            ($productNames[$productId] ?? 'Deleted product') => $count,
        ]);

        $topClicks = UserActivity::where('event', 'like', 'click:%')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->selectRaw('event, COUNT(*) as c')
            ->groupBy('event')
            ->orderByDesc('c')
            ->pluck('c', 'event');

        $sessionsQuery = SiteVisit::where('last_seen', '>=', $since)->whereNotNull('user_id');
        $registeredCount = (clone $sessionsQuery)->count();

        $deviceCounts = (clone $sessionsQuery)->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as c')
            ->groupBy('device_type')
            ->orderByDesc('c')
            ->pluck('c', 'device_type');

        $locationCounts = (clone $sessionsQuery)->whereNotNull('country')
            ->selectRaw("COALESCE(city, '?') as city, country, COUNT(*) as c")
            ->groupBy('city', 'country')
            ->orderByDesc('c')
            ->take(10)
            ->get();

        // shaped here (not inline in the blade) — same convention as VisitorController's own
        // $chartData, and simpler for the view to just @json() a single ready-made variable
        $chartData = [
            'devices' => [
                'labels' => $deviceCounts->keys()->map(fn ($d) => ucfirst($d))->values(),
                'values' => $deviceCounts->values()->values(),
            ],
            'clicks' => [
                'labels' => $topClicks->keys()->map(fn ($k) => str_replace('click:', '', $k))->values(),
                'values' => $topClicks->values()->values(),
            ],
        ];

        return view('admin.analytics.index', [
            'search' => $search,
            'activities' => $activities,
            'retentionDays' => self::RETENTION_DAYS,
            'topPages' => $topPages,
            'topProducts' => $topProducts,
            'topClicks' => $topClicks,
            'registeredCount' => $registeredCount,
            'deviceCounts' => $deviceCounts,
            'chartData' => $chartData,
            'locationCounts' => $locationCounts,
        ]);
    }
}
