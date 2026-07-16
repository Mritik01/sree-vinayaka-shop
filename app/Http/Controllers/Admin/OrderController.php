<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use App\Models\SupportMessage;
use App\Notifications\OrderCancelled;
use App\Services\OrderAutoCancelService;
use App\Services\RefundService;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

                // "#42" or "42" also matches the order id directly, and so does its
                // "MKB-2026-000042" order number or "TXID00000042" transaction id
                if (($id = (int) ltrim($search, '#')) > 0) {
                    $q->orWhere('id', $id);
                }
                if (($orderNumberId = Order::idFromOrderNumber($search)) !== null) {
                    $q->orWhere('id', $orderNumberId);
                }
                if (($transactionOrderId = Order::idFromTransactionId($search)) !== null) {
                    $q->orWhere('id', $transactionOrderId);
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
            'order_number' => $order->orderNumber(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'items_count' => $order->items->count(),
            'coupon_code' => $order->coupon->code ?? null,
            'total' => (int) $order->total,
            'status' => $order->status,
            'payment_method' => strtoupper($order->payment_method ?? 'COD'),
            'payment_status' => $order->payment_status,
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

        // a razorpay order row exists in the DB the instant "Pay Online" is clicked, well before
        // the customer actually completes payment (or abandons/fails it) — it must not count as
        // "needs your attention" until payment_status actually confirms as paid. COD needs no such
        // gate, since there's no payment step to wait on.
        $actionable = fn ($q) => $q->where(function ($q2) {
            $q2->where('payment_method', '!=', 'razorpay')->orWhere('payment_status', 'paid');
        });

        // support-chat heartbeat, piggybacked on this same poll so a customer message chimes
        // on whichever admin page is open — latest_id is the client's watermark for "new
        // message arrived", latest carries just enough to render the toast without a 2nd fetch
        $latestCustomerMessage = SupportMessage::where('sender', 'customer')->latest('id')->with('order')->first();

        return response()->json([
            'server_now' => now()->valueOf(),
            'latest_id' => (int) (Order::max('id') ?? 0),
            'pending_count' => $actionable(Order::where('status', 'pending'))->count(),
            'support' => [
                'unread' => SupportMessage::where('sender', 'customer')->whereNull('read_at')->count(),
                'latest_id' => (int) ($latestCustomerMessage->id ?? 0),
                'latest' => $latestCustomerMessage ? [
                    'order_id' => $latestCustomerMessage->order_id,
                    'order_number' => $latestCustomerMessage->order->orderNumber(),
                    'customer_name' => $latestCustomerMessage->order->customer_name,
                    'snippet' => $latestCustomerMessage->message !== '' ? Str::limit($latestCustomerMessage->message, 80) : '📷 Photo',
                ] : null,
            ],
            'auto_cancelled' => $autoCancelled->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->orderNumber(),
                'customer_name' => $order->customer_name,
                'total' => (int) $order->total,
            ])->values(),
            'orders' => $changedOrders->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->orderNumber(),
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'delivery_address' => $order->delivery_address,
                'distance_km' => $order->distance_km !== null ? number_format($order->distance_km, 1) : null,
                'total' => (int) $order->total,
                'status' => $order->status,
                'payment_method' => strtoupper($order->payment_method ?? 'COD'),
                'payment_status' => $order->payment_status,
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
            'order_number' => $order->orderNumber(),
            'status' => $order->status,
            'payment_method' => strtoupper($order->payment_method ?? 'COD'),
            'payment_status' => $order->payment_status,
            'cancelled_by' => $order->cancelled_by,
            'cancellation_reason' => $order->cancellation_reason,
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
            ? "Order {$order->orderNumber()} assigned to {$rider->name}."
            : "Order {$order->orderNumber()} unassigned.");
    }

    // initiates a real refund through Razorpay against the payment already captured for this
    // order — partial refunds accumulate (refunded_amount) until the full total is reached
    public function refund(Request $request, Order $order)
    {
        if (!$order->isRefundable()) {
            return back()->with('status', "Order {$order->orderNumber()} has nothing left to refund.");
        }

        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:'.$order->refundableAmount()],
        ]);

        $result = RefundService::refund($order, $data['amount'] ?? null);

        return back()->with('status', $result['message']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', Order::STATUSES),
            'eta_minutes' => 'nullable|integer|min:5|max:240',
            'cancellation_reason' => 'nullable|string|max:255',
        ]);

        // delivered/cancelled are terminal — a stale "New Order" popup (or a second admin tab)
        // must never resurrect a closed order, e.g. re-confirming one the customer already cancelled
        if (in_array($order->status, ['delivered', 'cancelled']) && $order->status !== $data['status']) {
            $label = $order->status === 'delivered' ? 'delivered' : 'cancelled';
            $message = "Order {$order->orderNumber()} is already {$label} — its status was not changed.";

            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 409);
            }

            return back()->with('status', $message);
        }

        // a rider has to be assigned before an order can head out — otherwise "out for delivery"
        // means nobody is actually carrying it
        if ($data['status'] === 'out_for_delivery' && $order->status !== 'out_for_delivery' && !$order->rider_id) {
            $message = "Order {$order->orderNumber()} needs a rider assigned before it can go out for delivery.";

            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('status', $message);
        }

        // a razorpay order can't be confirmed until Razorpay actually confirms the payment —
        // the row exists the instant "Pay Online" is clicked, well before that happens
        if ($data['status'] === 'confirmed' && $order->status !== 'confirmed' && $order->payment_method === 'razorpay' && $order->payment_status !== 'paid') {
            $message = "Order {$order->orderNumber()} can't be confirmed yet — Razorpay hasn't confirmed the payment.";

            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('status', $message);
        }

        $wasCancelled = $order->status === 'cancelled';
        $wasDelivered = $order->status === 'delivered';

        if ($data['status'] === 'cancelled' && !$wasCancelled) {
            $order->cancelled_by = 'admin';
            $order->cancellation_reason = trim($data['cancellation_reason'] ?? '') ?: null;
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

        // an order that was already paid online gets refunded in full the moment it's cancelled —
        // customers shouldn't have to notice/ask for money the shop never actually earned
        $statusMessage = 'Order status updated.';
        if (!$wasCancelled && $order->status === 'cancelled') {
            RefundService::autoRefundOnCancel($order);
            $order->refresh();
            if ($order->refund_status !== 'none') {
                $statusMessage .= ' ₹'.number_format($order->refunded_amount).' refunded automatically.';
            }

            // every admin gets the alert, including whoever just clicked cancel — with more than
            // one admin account, the others need to know too, and it's a no-op nuisance at worst
            // for the one who already knows (it just confirms in their own notification center)
            OrderCancelled::notifyAdmins($order);
        }

        // advance the customer's reward-stamp progress the moment an order first lands as delivered
        if (!$wasDelivered && $order->status === 'delivered' && $order->user) {
            RewardService::recordDelivery($order->user);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $order->status]);
        }

        return back()->with('status', $statusMessage);
    }
}
