<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use App\Services\OrderAutoCancelService;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    use PaginatesAdminLists;

    // how many of a customer's next orders are forced to online payment after a COD order of
    // theirs gets cancelled (i.e. they didn't accept/take delivery of it) — see checkout.store()
    private const COD_BLOCK_STRIKES = 2;

    public function index(Request $request)
    {
        $query = Order::with(['items', 'coupon'])->latest();

        if ($request->filled('status') && in_array($request->status, Order::STATUSES)) {
            $query->where('status', $request->status);
        }

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_address', 'like', "%{$search}%");

                // "#42" or "42" also matches the order id directly
                if (($id = (int) ltrim($search, '#')) > 0) {
                    $q->orWhere('id', $id);
                }
            });
        }

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        // live keystroke search — the table is Alpine-array-driven (for the real-time new-order
        // insert feature), so instead of an HTML swap we just hand back fresh row data as JSON
        if ($request->ajax()) {
            return response()->json([
                'orders' => collect($orders->items())->map(fn ($order) => $this->orderRowForJs($order))->values(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
            ]);
        }

        return view('admin.orders.index', [
            'orders' => $orders,
            'statusFilter' => $request->status,
            'search' => $search,
        ]);
    }

    private function orderRowForJs(Order $order): array
    {
        return [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'items_count' => $order->items->count(),
            'coupon_code' => $order->coupon->code ?? null,
            'total' => (int) $order->total,
            'status' => $order->status,
            'is_gift_order' => $order->is_gift_order,
            'created_at' => $order->created_at->format('d M, h:i A'),
            // unix ms the delivery was promised for; the client counts down against it,
            // same value the customer's own tracking page uses (see OrderTrackingController)
            'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                : null,
        ];
    }

    public function poll(Request $request)
    {
        // piggybacks the 90-min stale-order sweep onto the admin's existing 5s poll, so it takes
        // effect immediately whenever an admin has the panel open — the scheduled command (see
        // Kernel::schedule) is what actually guarantees it runs even with no admin logged in
        $autoCancelled = OrderAutoCancelService::cancelStaleOrders();

        // time-cursor based (not id-based) so this single query covers both brand-new orders AND
        // status/rider/photo changes on orders the admin already has on screen (e.g. a rider
        // marking one delivered) — the client partitions "new" vs "updated" itself using the id
        $since = $request->filled('since')
            ? Carbon::createFromTimestampMs((int) $request->query('since'))
            : now()->subSeconds(10);
        $changedOrders = Order::with(['items', 'coupon'])->where('updated_at', '>=', $since)->orderBy('id')->get();

        return response()->json([
            'server_now' => now()->valueOf(),
            'latest_id' => (int) (Order::max('id') ?? 0),
            'pending_count' => Order::where('status', 'pending')->count(),
            'auto_cancelled' => $autoCancelled->map(fn ($order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'total' => (int) $order->total,
            ])->values(),
            'orders' => $changedOrders->map(fn ($order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'delivery_address' => $order->delivery_address,
                'distance_km' => $order->distance_km !== null ? number_format($order->distance_km, 1) : null,
                'total' => (int) $order->total,
                'status' => $order->status,
                'coupon_code' => $order->coupon->code ?? null,
                'items_count' => $order->items->count(),
                'is_gift_order' => $order->is_gift_order,
                'created_at' => $order->created_at->format('d M, h:i A'),
                // unix ms the delivery was promised for; the client counts down against it
                'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                    ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                    : null,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'is_gift' => $item->is_gift,
                ])->values(),
            ])->values(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'coupon', 'user', 'rider']);

        $etaEndsAt = ($order->confirmed_at && $order->eta_minutes)
            ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
            : null;

        return view('admin.orders.show', [
            'order' => $order,
            'orderForJs' => $this->orderDetailForJs($order),
            'etaEndsAt' => $etaEndsAt,
            'riders' => Rider::orderBy('name')->get(),
        ]);
    }

    // polled by the order-detail page so rider-side changes (status, delivery photo) show up
    // live without the admin needing to reload — see resources/js/admin.js: adminOrderShowPage()
    public function status(Order $order)
    {
        $order->load('rider');

        return response()->json(['ok' => true, 'order' => $this->orderDetailForJs($order)]);
    }

    private function orderDetailForJs(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'cancelled_by' => $order->cancelled_by,
            'rider_name' => $order->rider->name ?? null,
            'placed_at' => $order->created_at->format('h:i A'),
            'confirmed_at' => $order->confirmed_at?->format('h:i A'),
            'out_for_delivery_at' => $order->out_for_delivery_at?->format('h:i A'),
            'delivered_at' => $order->delivered_at?->format('h:i A'),
            'delivered_at_full' => $order->delivered_at?->format('d M, h:i A'),
            'cancelled_at' => $order->cancelled_at?->format('h:i A'),
            'cancelled_at_full' => $order->cancelled_at?->format('d M, h:i A'),
            'delivery_photo_url' => $order->delivery_photo_path ? asset($order->delivery_photo_path) : null,
            'delivery_photo_uploaded_at' => $order->delivery_photo_uploaded_at?->format('d M, h:i A'),
        ];
    }

    // a rider's delivery queue only ever shows orders an admin has explicitly assigned to them
    // (see Rider\OrderController::index) — this is the only place that assignment happens
    public function assignRider(Request $request, Order $order)
    {
        $data = $request->validate([
            'rider_id' => 'nullable|exists:riders,id',
        ]);

        $order->rider_id = $data['rider_id'] ?: null;
        $order->save();

        $rider = $order->rider_id ? Rider::find($order->rider_id) : null;

        return back()->with('status', $rider
            ? "Order #{$order->id} assigned to {$rider->name}."
            : "Order #{$order->id} unassigned.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', Order::STATUSES),
            'eta_minutes' => 'nullable|integer|min:5|max:240',
        ]);

        // delivered/cancelled are terminal — a stale "New Order" popup (or a second admin tab)
        // must never resurrect a closed order, e.g. re-confirming one the customer already cancelled
        if (in_array($order->status, ['delivered', 'cancelled']) && $order->status !== $data['status']) {
            $label = $order->status === 'delivered' ? 'delivered' : 'cancelled';
            $message = "Order #{$order->id} is already {$label} — its status was not changed.";

            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 409);
            }

            return back()->with('status', $message);
        }

        $wasCancelled = $order->status === 'cancelled';
        $wasDelivered = $order->status === 'delivered';

        if ($data['status'] === 'cancelled' && !$wasCancelled) {
            $order->cancelled_by = 'admin';
        }
        if (!empty($data['eta_minutes'])) {
            $order->eta_minutes = $data['eta_minutes'];
        }
        $order->status = $data['status'];
        $order->save();
        $order->stampStatusTimestamp();

        // a COD order the ADMIN cancels (customer didn't accept/take delivery of it) restricts
        // that customer to online-payment-only for their next few orders, to discourage spam —
        // a customer cancelling their own order (OrderTrackingController::cancel) never does this
        if (!$wasCancelled && $order->status === 'cancelled' && $order->payment_method === 'cod' && $order->user) {
            $order->user->forceFill(['cod_blocked_orders' => self::COD_BLOCK_STRIKES])->save();
        }

        // advance the customer's reward-stamp progress the moment an order first lands as delivered
        if (!$wasDelivered && $order->status === 'delivered' && $order->user) {
            RewardService::recordDelivery($order->user);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $order->status]);
        }

        return back()->with('status', 'Order status updated.');
    }
}
