<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserActivity;
use App\Notifications\AdminMessage;
use App\Services\CustomerBlockService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CustomerController extends Controller
{
    use PaginatesAdminLists;

    private const SORTS = ['spent', 'orders', 'recent', 'newest', 'name'];

    public function index(Request $request)
    {
        $sort = in_array($request->get('sort'), self::SORTS, true) ? $request->get('sort') : 'spent';
        $search = trim((string) $request->get('q', ''));
        $statusFilter = in_array($request->get('status'), ['active', 'blocked'], true) ? $request->get('status') : '';

        $customers = User::query()
            ->withCount(['orders as total_orders' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->with('latestOrder')
            ->get()
            ->each(fn ($user) => $user->total_spent = (int) ($user->total_spent ?? 0));

        $customers = (match ($sort) {
            'orders' => $customers->sortByDesc('total_orders'),
            'recent' => $customers->sortByDesc(fn ($u) => $u->latestOrder?->created_at),
            'newest' => $customers->sortByDesc('created_at'),
            'name' => $customers->sortBy(fn ($u) => strtolower($u->name)),
            default => $customers->sortByDesc('total_spent'),
        })->values();

        $mvp = [
            'today' => $this->mostValuable(now()->startOfDay()),
            'week' => $this->mostValuable(now()->startOfWeek(Carbon::MONDAY)),
            'allTime' => $this->mostValuable(null),
        ];

        // chart/summary numbers always reflect the full customer base, never the search filter or current page
        $topSpenders = $customers->sortByDesc('total_spent')->take(8)->values();

        $newCustomersByDay = User::where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['id', 'created_at'])
            ->groupBy(fn ($u) => $u->created_at->toDateString());

        $window = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());

        $chartData = [
            'topSpenders' => [
                'labels' => $topSpenders->pluck('name')->values(),
                'values' => $topSpenders->pluck('total_spent')->values(),
            ],
            'newCustomers' => [
                'labels' => $window->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
                'values' => $window->map(fn ($d) => ($newCustomersByDay[$d] ?? collect())->count())->values(),
            ],
        ];

        $totalCustomers = $customers->count();
        $totalRevenue = (int) $customers->sum('total_spent');
        $blockedCount = $customers->where('is_blocked', true)->count();

        if ($statusFilter !== '') {
            $customers = $customers->where('is_blocked', $statusFilter === 'blocked')->values();
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $customers = $customers->filter(fn ($u) => str_contains(strtolower($u->name.' '.$u->phone), $needle))->values();
        }

        $perPage = $this->perPage($request);
        $page = (int) $request->get('page', 1);

        $paginatedCustomers = new LengthAwarePaginator(
            $customers->forPage($page, $perPage)->values(),
            $customers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $data = [
            'customers' => $paginatedCustomers,
            'sort' => $sort,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'mvp' => $mvp,
            'chartData' => $chartData,
            'totalCustomers' => $totalCustomers,
            'totalRevenue' => $totalRevenue,
            'blockedCount' => $blockedCount,
            'newThisWeek' => User::where('created_at', '>=', now()->startOfWeek(Carbon::MONDAY))->count(),
        ];

        return $request->ajax() ? view('admin.customers._results', $data) : view('admin.customers.index', $data);
    }

    public function show(User $user)
    {
        $user->loadCount(['orders as total_orders' => fn ($q) => $q->where('status', '!=', 'cancelled')]);
        $user->loadSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total');
        $user->total_spent = (int) ($user->total_spent ?? 0);

        $orders = $user->orders()->with('items')->latest()->get();
        $addresses = $orders->pluck('delivery_address')->filter()->unique()->values();

        $monthly = $orders->where('status', '!=', 'cancelled')
            ->sortBy('created_at')
            ->groupBy(fn ($o) => $o->created_at->format('M Y'))
            ->map(fn ($group) => (int) $group->sum('total'));

        $mvpBadges = [
            'today' => $this->isMvp($user, now()->startOfDay()),
            'week' => $this->isMvp($user, now()->startOfWeek(Carbon::MONDAY)),
            'allTime' => $this->isMvp($user, null),
        ];

        $assignedCoupons = $user->assignedCoupons()->orderByDesc('coupon_user.created_at')->get();
        // only single-use coupons make sense to hand-assign here — "once per user" coupons are
        // meant to stay open to every customer automatically (see CheckoutController::show()),
        // and a single-use coupon can only ever belong to one customer at a time (once anyone
        // redeems it, it's gone system-wide, so a second assignee would just be a dead option)
        $assignableCoupons = Coupon::whereNotIn('id', $assignedCoupons->pluck('id'))
            ->where('is_active', true)
            ->where('expires_at', '>=', now())
            ->where('usage_type', 'single_use')
            ->whereDoesntHave('assignedUsers')
            ->orderBy('code')
            ->get(['id', 'code', 'description']);

        return view('admin.customers.show', array_merge([
            'customer' => $user,
            'orders' => $orders,
            'addresses' => $addresses,
            'monthlyLabels' => $monthly->keys()->values(),
            'monthlyValues' => $monthly->values()->values(),
            'mvpBadges' => $mvpBadges,
            'assignedCoupons' => $assignedCoupons,
            'assignableCoupons' => $assignableCoupons,
            'blockHistory' => $user->blockHistory()->with('admin')->get(),
        ], $this->behaviourData($user)));
    }

    // any logged-in admin can block/unblock (not super-admin-only) — same tier as cancelling an
    // order or issuing a refund, both already unrestricted admin actions in this app
    public function block(Request $request, User $user)
    {
        $data = $request->validate([
            'reason' => 'required|in:'.implode(',', array_keys(User::BLOCK_REASONS)),
            'message' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
            'notes' => 'nullable|string|max:1000',
        ], [
            'message.required_if' => 'Please write a message for the customer since you chose "Other (Custom Reason)".',
        ]);

        // every reason except 'other' has a sensible, clear default — falling back to it here
        // (rather than only in the UI) guarantees block_message is never blank in the database,
        // regardless of how this endpoint is called
        $message = trim($data['message'] ?? '') ?: User::defaultBlockMessageFor($data['reason']);

        CustomerBlockService::block(
            $user,
            Auth::guard('admin')->user(),
            $data['reason'],
            $message,
            trim($data['notes'] ?? '') ?: null
        );

        // called exclusively via fetch() from blockCustomerModal() (see admin.js) — a redirect
        // response here (e.g. back()) gets auto-followed by the browser as a same-method PATCH
        // to the redirect target, which 405s since that route only accepts GET/HEAD. A plain
        // JSON response, matching updateName() below, sidesteps that entirely.
        return response()->json(['ok' => true, 'message' => "{$user->name} has been blocked."]);
    }

    public function unblock(Request $request, User $user)
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        CustomerBlockService::unblock($user, Auth::guard('admin')->user(), trim($data['notes'] ?? '') ?: null);

        return back()->with('status', "{$user->name} has been unblocked and regains normal access immediately.");
    }

    // "attach a coupon to just this customer" — the moment a coupon has any row here, Coupon::isRestricted()
    // flips true and it stops being redeemable by anyone else (see CouponController::apply()).
    public function attachCoupon(Request $request, User $user)
    {
        $data = $request->validate([
            'coupon_id' => 'required|integer|exists:coupons,id',
        ]);

        $coupon = Coupon::findOrFail($data['coupon_id']);

        if ($coupon->usage_type !== 'single_use') {
            return back()->withErrors([
                'coupon_id' => "{$coupon->code} is a \"once per user\" coupon — those stay open to every customer automatically and can't be assigned to just one person.",
            ])->withInput();
        }

        $assignedToSomeoneElse = $coupon->assignedUsers()->where('users.id', '!=', $user->id)->exists();

        if ($assignedToSomeoneElse) {
            return back()->withErrors([
                'coupon_id' => "{$coupon->code} is a single-use coupon already assigned to another customer — switch it to \"Once per user\" first if you want to share it.",
            ])->withInput();
        }

        $user->assignedCoupons()->syncWithoutDetaching([$coupon->id]);

        return back()->with('status', 'Coupon assigned to '.$user->name.'.');
    }

    public function detachCoupon(User $user, Coupon $coupon)
    {
        $user->assignedCoupons()->detach($coupon->id);

        return back()->with('status', 'Coupon removed from '.$user->name.'.');
    }

    public function clearCodRestriction(User $user)
    {
        $user->forceFill(['cod_blocked_orders' => 0])->save();

        return back()->with('status', 'COD restriction cleared for '.$user->name.'.');
    }

    public function updateName(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user->forceFill(['name' => trim($data['name'])])->save();

        return response()->json(['ok' => true, 'name' => $user->name]);
    }

    public function notify(Request $request, User $user)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
        ]);

        $user->notify(new AdminMessage($data['title'], $data['message']));

        return response()->json(['ok' => true, 'sent' => 1]);
    }

    public function notifyAll(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
        ]);

        $users = User::all();
        Notification::send($users, new AdminMessage($data['title'], $data['message']));

        return response()->json(['ok' => true, 'sent' => $users->count()]);
    }

    // stable lifetime stats (first/last seen, session count, device mix) come from site_visits — one row
    // per session, never pruned. The detailed action-by-action feed comes from user_activities, which is
    // capped to the most recent 15 rows per user (see ActivityLogger::pruneOldActivity) so that table can't
    // grow unbounded — so "most viewed products" / "most visited pages" reflect only that recent window.
    private function behaviourData(User $user): array
    {
        $activities = UserActivity::where('user_id', $user->id)
            ->with('product:id,name,slug')
            ->latest('created_at')
            ->take(15)
            ->get();

        $visits = SiteVisit::where('user_id', $user->id)->get();

        $deviceCounts = $visits->countBy('device_type')->filter(fn ($c, $k) => $k)->sortDesc();
        $browserCounts = $visits->countBy('browser')->filter(fn ($c, $k) => $k)->sortDesc();
        $platformCounts = $visits->countBy('platform')->filter(fn ($c, $k) => $k)->sortDesc();

        $mostViewedProducts = UserActivity::where('user_id', $user->id)
            ->where('event', 'product_view')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, COUNT(*) as views')
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->with('product:id,name')
            ->take(5)
            ->get();

        $topPages = UserActivity::where('user_id', $user->id)
            ->where('event', 'page_view')
            ->whereNotNull('label')
            ->selectRaw('label, COUNT(*) as c')
            ->groupBy('label')
            ->orderByDesc('c')
            ->take(6)
            ->pluck('c', 'label');

        $window = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $visitsByDay = $visits->groupBy(fn ($v) => $v->first_seen->toDateString());

        $lastSeen = $visits->max('last_seen');

        return [
            'behaviour' => [
                'firstSeen' => $visits->min('first_seen'),
                'lastSeen' => $lastSeen,
                'isOnline' => $lastSeen && $lastSeen->gt(now()->subMinutes(5)),
                'sessionsCount' => $visits->count(),
                'totalPageViews' => (int) $visits->sum('page_views'),
                'primaryDevice' => $deviceCounts->keys()->first(),
                'primaryBrowser' => $browserCounts->keys()->first(),
                'primaryPlatform' => $platformCounts->keys()->first(),
                'topPages' => $topPages,
                'mostViewedProducts' => $mostViewedProducts,
                'activities' => $activities,
            ],
            'behaviourChart' => [
                'activity' => [
                    'labels' => $window->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
                    'values' => $window->map(fn ($d) => ($visitsByDay[$d] ?? collect())->count())->values(),
                ],
                'devices' => [
                    'labels' => $deviceCounts->keys()->map(fn ($d) => ucfirst($d))->values(),
                    'values' => $deviceCounts->values()->values(),
                ],
            ],
        ];
    }

    private function mostValuable(?Carbon $from): ?array
    {
        $row = Order::where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->selectRaw('user_id, SUM(total) as spent, COUNT(*) as orders_count')
            ->groupBy('user_id')
            ->orderByDesc('spent')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'user' => User::find($row->user_id),
            'spent' => (int) $row->spent,
            'orders_count' => (int) $row->orders_count,
        ];
    }

    private function isMvp(User $user, ?Carbon $from): bool
    {
        $top = $this->mostValuable($from);

        return $top && $top['user']?->id === $user->id;
    }
}
