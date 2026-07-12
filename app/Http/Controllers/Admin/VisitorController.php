<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VisitorController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $visitsQuery = SiteVisit::with('user:id,name,phone')->latest('last_seen');

        if ($search !== '') {
            $visitsQuery->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('device_type', 'like', "%{$search}%")
                    ->orWhere('browser', 'like', "%{$search}%")
                    ->orWhere('platform', 'like', "%{$search}%")
                    ->orWhere('entry_path', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $visits = $visitsQuery->paginate($this->perPage($request))->withQueryString();

        // an AJAX live-search request only ever needs the results partial — skip the
        // stats/chart queries entirely so keystrokes stay fast
        if ($request->ajax()) {
            return view('admin.visitors._results', ['visits' => $visits, 'search' => $search]);
        }

        $totalToday = SiteVisit::whereDate('first_seen', today())->count();
        $totalWeek = SiteVisit::where('first_seen', '>=', now()->startOfWeek(Carbon::MONDAY))->count();
        $totalAllTime = SiteVisit::count();

        $guestCount = SiteVisit::whereNull('user_id')->count();
        $registeredCount = SiteVisit::whereNotNull('user_id')->count();

        $deviceCounts = SiteVisit::whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as c')
            ->groupBy('device_type')
            ->orderByDesc('c')
            ->pluck('c', 'device_type');

        $browserCounts = SiteVisit::whereNotNull('browser')
            ->selectRaw('browser, COUNT(*) as c')
            ->groupBy('browser')
            ->orderByDesc('c')
            ->take(6)
            ->pluck('c', 'browser');

        $window = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $visitsByDay = SiteVisit::where('first_seen', '>=', now()->subDays(13)->startOfDay())
            ->get(['first_seen'])
            ->groupBy(fn ($v) => $v->first_seen->toDateString());

        $chartData = [
            'visits' => [
                'labels' => $window->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
                'values' => $window->map(fn ($d) => ($visitsByDay[$d] ?? collect())->count())->values(),
            ],
            'devices' => [
                'labels' => $deviceCounts->keys()->map(fn ($d) => ucfirst($d))->values(),
                'values' => $deviceCounts->values()->values(),
            ],
            'browsers' => [
                'labels' => $browserCounts->keys()->values(),
                'values' => $browserCounts->values()->values(),
            ],
        ];

        return view('admin.visitors.index', [
            'search' => $search,
            'totalToday' => $totalToday,
            'totalWeek' => $totalWeek,
            'totalAllTime' => $totalAllTime,
            'guestCount' => $guestCount,
            'registeredCount' => $registeredCount,
            'chartData' => $chartData,
            'visits' => $visits,
        ]);
    }
}
