<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Rider;
use App\Models\SupportMessage;
use App\Notifications\ItemsRemovedFromOrder;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderItemsAdded;
use App\Notifications\RiderAssigned;
use App\Services\OrderAutoCancelService;
use App\Services\PlatformIncomeService;
use App\Services\RefundService;
use App\Services\RewardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
{
    use PaginatesAdminLists;

    // how many of a customer's next orders are forced to online payment after a COD order of
    // theirs gets cancelled (i.e. they didn't accept/take delivery of it) — see checkout.store()
    private const COD_BLOCK_STRIKES = 2;

    // Quick Filters — "Last N Days/Months" are rolling windows ending today (today counts as
    // day/month 1), while bare "This/Last Week|Month|Year" are calendar-aligned periods. Kept as
    // its own method (rather than inline in applyOrderFilters) so exportExcel's filename can ask
    // "what date range does this request actually resolve to" without duplicating the match.
    private function resolveDateRange(Request $request): ?array
    {
        $quick = (string) $request->get('quick_filter', '');
        $now = now();

        if ($quick === 'custom') {
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

            if ($from && $to) {
                return [$from, $to];
            }
            if ($from) {
                return [$from, $now->copy()->endOfDay()];
            }
            if ($to) {
                return [Carbon::createFromTimestamp(0), $to];
            }

            return null;
        }

        return match ($quick) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_2_days' => [$now->copy()->subDays(1)->startOfDay(), $now->copy()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_3_months' => [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay()],
            'last_6_months' => [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => null,
        };
    }

    // single filter-building path shared by index() (both the page load and its AJAX refresh),
    // the stats aggregate, and exportExcel() — guarantees the export can never drift from what's
    // actually on screen, since both read through this exact same method
    private function applyOrderFilters(Builder $query, Request $request): Builder
    {
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

        if ($range = $this->resolveDateRange($request)) {
            $query->whereBetween('created_at', $range);
        }

        if ($request->filled('payment_status') && in_array($request->payment_status, ['pending', 'paid', 'failed'])) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method') && in_array($request->payment_method, ['cod', 'razorpay'])) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('rider_id')) {
            $query->where('rider_id', (int) $request->rider_id);
        }

        if ($request->filled('amount_min')) {
            $query->where('total', '>=', (int) $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('total', '<=', (int) $request->amount_max);
        }

        return $query;
    }

    // one aggregate query (conditional SUMs) instead of 8 separate count()/sum() round-trips —
    // runs against the exact same filtered set the table/export show, just before pagination
    private function computeStats(Builder $query): array
    {
        $row = $query->selectRaw("
            COUNT(*) as total_orders,
            COALESCE(SUM(CASE WHEN status IN ('confirmed', 'out_for_delivery', 'delivered') THEN total ELSE 0 END), 0) as total_sales,
            SUM(CASE WHEN status IN ('confirmed', 'out_for_delivery', 'delivered') THEN 1 ELSE 0 END) as sales_order_count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(CASE WHEN payment_method = 'cod' THEN 1 ELSE 0 END) as cod_orders,
            SUM(CASE WHEN payment_method = 'razorpay' THEN 1 ELSE 0 END) as online_orders
        ")->first();

        $totalSales = (int) $row->total_sales;
        $salesOrderCount = (int) $row->sales_order_count;

        return [
            'total_orders' => (int) $row->total_orders,
            'total_sales' => $totalSales,
            'delivered_orders' => (int) $row->delivered_orders,
            'pending_orders' => (int) $row->pending_orders,
            'cancelled_orders' => (int) $row->cancelled_orders,
            'cod_orders' => (int) $row->cod_orders,
            'online_orders' => (int) $row->online_orders,
            'avg_order_value' => $salesOrderCount > 0 ? (int) round($totalSales / $salesOrderCount) : 0,
        ];
    }

    public function index(Request $request)
    {
        $stats = $this->computeStats($this->applyOrderFilters(Order::query(), $request));

        $orders = $this->applyOrderFilters(Order::with(['items', 'coupon']), $request)
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        // live keystroke search — the table is Alpine-array-driven (for the real-time new-order
        // insert feature), so instead of an HTML swap we just hand back fresh row data as JSON
        if ($request->ajax()) {
            return response()->json([
                'orders' => collect($orders->items())->map(fn ($order) => $this->orderRowForJs($order))->values(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'stats' => $stats,
            ]);
        }

        return view('admin.orders.index', [
            'orders' => $orders,
            'statusFilter' => $request->status,
            'search' => $request->get('q', ''),
            'stats' => $stats,
            'riders' => Rider::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'quick_filter' => $request->get('quick_filter', ''),
                'from' => $request->get('from', ''),
                'to' => $request->get('to', ''),
                'payment_status' => $request->get('payment_status', ''),
                'payment_method' => $request->get('payment_method', ''),
                'rider_id' => $request->get('rider_id', ''),
                'amount_min' => $request->get('amount_min', ''),
                'amount_max' => $request->get('amount_max', ''),
            ],
        ]);
    }

    // mirrors index()'s exact filter set (see applyOrderFilters) — "export only the currently
    // filtered data" is guaranteed by construction, not by keeping two filter implementations
    // in sync by hand. Streamed + chunked so a large date range doesn't load every matching
    // order into memory at once.
    public function exportExcel(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request)
            ?? $this->exportFallbackDateBounds($request);

        $filename = 'Orders_'.$fromDate->format('d-m-Y').'_to_'.$toDate->format('d-m-Y').'.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orders');

        $headers = [
            'Order ID', 'Customer Name', 'Mobile Number', 'Order Date & Time', 'Order Status',
            'Payment Status', 'Payment Method', 'Total Amount', 'Delivery Charge', 'Discount',
            'Delivery Partner', 'Delivery Date', 'Number of Products',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7A1622');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $dateFormat = 'dd-mm-yyyy hh:mm AM/PM';
        $currencyFormat = '₹#,##0';

        $rowNum = 2;
        $query = $this->applyOrderFilters(
            Order::with(['items:id,order_id,removed_at', 'rider:id,name', 'fees:id,order_id,key,amount']),
            $request
        )->latest();

        $query->chunk(500, function ($orders) use ($sheet, &$rowNum) {
            foreach ($orders as $order) {
                $deliveryCharge = (int) ($order->fees->firstWhere('key', 'delivery')->amount ?? 0);

                $sheet->setCellValue("A{$rowNum}", $order->orderNumber());
                $sheet->setCellValue("B{$rowNum}", $order->customer_name);
                $sheet->setCellValueExplicit("C{$rowNum}", $order->customer_phone, DataType::TYPE_STRING);
                $sheet->setCellValue("D{$rowNum}", ExcelDate::PHPToExcel($order->created_at));
                $sheet->setCellValue("E{$rowNum}", ucfirst(str_replace('_', ' ', $order->status)));
                $sheet->setCellValue("F{$rowNum}", ucfirst($order->payment_status ?? 'pending'));
                $sheet->setCellValue("G{$rowNum}", strtoupper($order->payment_method ?? 'cod'));
                $sheet->setCellValue("H{$rowNum}", (int) $order->total);
                $sheet->setCellValue("I{$rowNum}", $deliveryCharge);
                $sheet->setCellValue("J{$rowNum}", (int) $order->discount_amount);
                $sheet->setCellValue("K{$rowNum}", $order->rider->name ?? '—');
                if ($order->delivered_at) {
                    $sheet->setCellValue("L{$rowNum}", ExcelDate::PHPToExcel($order->delivered_at));
                } else {
                    $sheet->setCellValue("L{$rowNum}", '—');
                }
                $sheet->setCellValue("M{$rowNum}", $order->items->whereNull('removed_at')->count());

                $rowNum++;
            }
        });

        $lastRow = $rowNum - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("D2:D{$lastRow}")->getNumberFormat()->setFormatCode($dateFormat);
            $sheet->getStyle("L2:L{$lastRow}")->getNumberFormat()->setFormatCode($dateFormat);
            $sheet->getStyle("H2:J{$lastRow}")->getNumberFormat()->setFormatCode($currencyFormat);
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // when no quick/custom date filter is active, the filename still needs a meaningful range —
    // labelled with the earliest/latest order actually in the filtered set, falling back to
    // "today" for both ends if the filter matches nothing at all
    private function exportFallbackDateBounds(Request $request): array
    {
        $bounds = $this->applyOrderFilters(Order::query(), $request)
            ->selectRaw('MIN(created_at) as min_at, MAX(created_at) as max_at')
            ->first();

        return [
            $bounds->min_at ? Carbon::parse($bounds->min_at) : now(),
            $bounds->max_at ? Carbon::parse($bounds->max_at) : now(),
        ];
    }

    private function orderRowForJs(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->orderNumber(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'items_count' => $order->items->whereNull('removed_at')->count(),
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
                'items_count' => $order->items->whereNull('removed_at')->count(),
                'is_gift_order' => $order->is_gift_order,
                'created_at' => $order->created_at->format('d M, h:i A'),
                // unix ms the delivery was promised for; the client counts down against it
                'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                    ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                    : null,
                'items' => $order->items->whereNull('removed_at')->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'is_gift' => $item->is_gift,
                ])->values(),
            ])->values(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.removedByAdmin', 'items.addedByAdmin', 'coupon', 'user', 'rider', 'fees']);

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
        $order->load('rider', 'items');

        return response()->json(['ok' => true, 'order' => $this->orderDetailForJs($order)]);
    }

    private function orderDetailForJs(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->orderNumber(),
            'status' => $order->status,
            // internal packing checklist — keyed by order_item id so a second admin tab/device
            // packing the same order (or a page refresh) stays in sync; never sent anywhere
            // customer-facing (see OrderTrackingController, which has its own separate payload)
            'confirmed_items' => $order->items->mapWithKeys(fn ($item) => [$item->id => $item->confirmed_at !== null]),
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
    // internal packing checklist — an admin ticks each item off after physically verifying it
    // while packing. Purely a per-order_item timestamp: never surfaced to the customer, never
    // touches the order's real status/timeline (see the class-level note in OrderItem).
    public function confirmItem(Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        if (in_array($order->status, ['out_for_delivery', 'delivered', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'This order has already been dispatched, so the packing checklist is locked.'], 422);
        }

        $item->forceFill(['confirmed_at' => $item->confirmed_at ? null : now()])->save();

        return response()->json(['ok' => true, 'confirmed' => $item->confirmed_at !== null]);
    }

    public function confirmAllItems(Order $order)
    {
        if (in_array($order->status, ['out_for_delivery', 'delivered', 'cancelled'])) {
            return response()->json(['ok' => false, 'message' => 'This order has already been dispatched, so the packing checklist is locked.'], 422);
        }

        $order->items()->whereNull('confirmed_at')->update(['confirmed_at' => now()]);

        return response()->json([
            'ok' => true,
            'confirmed_items' => $order->items()->get()->mapWithKeys(fn ($item) => [$item->id => true]),
        ]);
    }

    // admin-only escape hatch for genuinely unavailable stock discovered after the order was
    // placed — removes just this line item (not the whole order), then recalculates totals so
    // the invoice/bill/customer view all reflect only what's still being fulfilled. Gated to
    // items not yet packed (see confirmItem() above) — once physically packed it's too late to
    // quietly drop it, and the admin should cancel/handle it another way instead.
    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        if ($item->removed_at !== null) {
            return back()->with('status', 'That item has already been removed.');
        }

        if ($item->confirmed_at !== null) {
            return back()->with('status', 'This item has already been packed and can no longer be removed.');
        }

        if (in_array($order->status, ['out_for_delivery', 'delivered', 'cancelled'], true)) {
            return back()->with('status', "Order {$order->orderNumber()} has already left the shop — items can no longer be removed.");
        }

        $remainingCount = $order->items()->whereNull('removed_at')->where('id', '!=', $item->id)->count();
        if ($remainingCount === 0) {
            return back()->with('status', 'Cannot remove the last item on an order — cancel the order instead.');
        }

        $data = $request->validate([
            'reason' => 'required|in:'.implode(',', array_keys(OrderItem::REMOVAL_REASONS)),
            'note' => 'nullable|string|max:255|required_if:reason,custom',
        ]);

        $item->forceFill([
            'removed_at' => now(),
            'removed_by_admin_id' => Auth::guard('admin')->id(),
            'removal_reason' => $data['reason'],
            'removal_reason_note' => trim($data['note'] ?? '') ?: null,
        ])->save();

        $order->recalculateTotals();

        // same locale-switch trick assignRider() already uses — the notification's title/message
        // are generated with __() at dispatch time, under the CUSTOMER's own locale
        if ($order->user) {
            $adminLocale = app()->getLocale();
            app()->setLocale($order->user->locale ?? 'en');
            $order->user->notify(new ItemsRemovedFromOrder($order, $item));
            app()->setLocale($adminLocale);
        }

        return back()->with('status', "{$item->product_name} removed from order {$order->orderNumber()} — totals updated.");
    }

    // admin adds one or more products to an order the customer already placed (usually a phoned-in
    // "add one more box"). Available only until the order leaves the shop — same gate as removeItem.
    // Prices are ALWAYS re-derived server-side via Product::priceForPortion() (never trusted from the
    // client), then Order::recalculateTotals() re-sums subtotal/discount/delivery/total exactly as it
    // does after a removal. Audit lives on the row (added_by_admin_id/added_at), matching removeItem.
    // Fetch/JSON endpoint (the modal reloads on success so the server-rendered item list refreshes).
    public function addItems(Request $request, Order $order)
    {
        if (in_array($order->status, ['out_for_delivery', 'delivered', 'cancelled'], true)) {
            return response()->json([
                'ok' => false,
                'message' => "Order {$order->orderNumber()} has already left the shop — products can no longer be added.",
            ], 422);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.portion' => 'required|integer|min:0',
            'items.*.quantity' => 'required|integer|min:1|max:99',
        ]);

        // resolve + validate every line first (all-or-nothing), so a single bad portion doesn't
        // leave the order half-updated
        $resolved = [];
        foreach ($data['items'] as $line) {
            $product = Product::find($line['product_id']);
            $portion = (int) $line['portion'];

            $allowedPortions = array_column($product->portionPriceList(), 'portion');
            if (!in_array($portion, $allowedPortions, true)) {
                return response()->json([
                    'ok' => false,
                    'message' => "\"{$product->name}\" isn't available in that portion. Please pick a listed variant.",
                ], 422);
            }

            $unit = $product->priceForPortion($portion ?: null);
            $resolved[] = [
                'product' => $product,
                'portion' => $portion,
                'quantity' => (int) $line['quantity'],
                'unit' => $unit,
            ];
        }

        $addedItems = collect();
        foreach ($resolved as $line) {
            $addedItems->push($order->items()->create([
                'product_id' => $line['product']->id,
                'product_name' => $line['product']->name,
                'product_price' => $line['unit'],
                'quantity' => $line['quantity'],
                'portion' => $line['portion'],
                'line_total' => $line['unit'] * $line['quantity'],
                'is_gift' => false,
                'confirmed_at' => null,
                'added_by_admin_id' => Auth::guard('admin')->id(),
                'added_at' => now(),
            ]));
        }

        $order->recalculateTotals();

        // notify the customer in-app, under their own locale — same idiom as removeItem/assignRider
        if ($order->user) {
            $adminLocale = app()->getLocale();
            app()->setLocale($order->user->locale ?? 'en');
            $order->user->notify(new OrderItemsAdded($order->fresh(), $addedItems));
            app()->setLocale($adminLocale);
        }

        $count = $addedItems->count();
        $message = $count === 1
            ? "{$addedItems->first()->product_name} added to order {$order->orderNumber()} — totals updated."
            : "{$count} items added to order {$order->orderNumber()} — totals updated.";

        // this endpoint responds JSON (the modal reloads the page on success rather than
        // following a redirect), but the flash still needs to survive into that reload so the
        // admin sees the same confirmation banner every other action on this page shows —
        // session()->flash() persists across the session cookie regardless of how this request
        // was made, so it's picked up by the reload exactly like back()->with('status', ...) is
        session()->flash('status', $message);

        return response()->json(['ok' => true, 'message' => $message]);
    }

    // audit trail lives on the order row itself (note_decided_by_admin_id/note_decided_at),
    // same as removeItem()'s removed_by_admin_id above — no separate activity log entry, matching
    // the rest of this controller's actions
    public function respondToNote(Request $request, Order $order)
    {
        if (!$order->customer_note || $order->note_status !== 'pending') {
            return back()->with('status', "Order {$order->orderNumber()} has no pending note to respond to.");
        }

        $data = $request->validate([
            'status' => 'required|in:accepted,denied',
            'message' => 'nullable|string|max:500',
        ]);

        $order->update([
            'note_status' => $data['status'],
            'note_decision_message' => trim($data['message'] ?? '') ?: null,
            'note_decided_by_admin_id' => Auth::guard('admin')->id(),
            'note_decided_at' => now(),
        ]);

        return back()->with('status', "Order {$order->orderNumber()}'s note has been {$data['status']}.");
    }

    public function assignRider(Request $request, Order $order)
    {
        $data = $request->validate([
            'rider_id' => 'nullable|exists:riders,id',
        ]);

        $previousRiderId = $order->rider_id;

        $order->rider_id = $data['rider_id'] ?: null;
        $order->save();

        $rider = $order->rider_id ? Rider::find($order->rider_id) : null;

        // notify the customer only on a genuine change to a real rider — not on every save of
        // this form, and not when unassigning. Reassigning to a different rider notifies again,
        // same as a first-time assignment (this is the sole hook a future SMS/WhatsApp send would
        // extend, but neither is wired up today per product decision)
        if ($rider && $order->rider_id !== $previousRiderId && $order->user) {
            // the notification's title/message are built with __() at dispatch time, so they must
            // be generated under the CUSTOMER's own locale, not whichever locale the admin doing
            // the assigning happens to be browsing in — switch briefly, then restore
            $adminLocale = app()->getLocale();
            app()->setLocale($order->user->locale ?? 'en');
            $order->user->notify(new RiderAssigned($order, $rider));
            app()->setLocale($adminLocale);
        }

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

        // platform income (₹15 fixed + 50% of the delivery charge) — see PlatformIncomeService
        if (!$wasDelivered && $order->status === 'delivered') {
            PlatformIncomeService::recordForDelivery($order);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $order->status]);
        }

        return back()->with('status', $statusMessage);
    }

    // super-admin only (see admin.super in routes/web.php) — a hard, permanent delete, not a
    // status change. Order::isDeletable() blocks any order that already has a platform income
    // record (i.e. was ever delivered), since that ledger is append-only and the FK constraint
    // has no cascade — cancel an order instead if it just needs to stop being active.
    public function destroy(Order $order)
    {
        if (!$order->isDeletable()) {
            return back()->with('status', "Order {$order->orderNumber()} has a permanent income record and can't be deleted — cancel it instead if it needs to be removed from active view.");
        }

        $orderNumber = $order->orderNumber();

        try {
            $order->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('status', "Order {$orderNumber} couldn't be deleted — other records still reference it.");
        }

        return redirect()->route('admin.orders.index')->with('status', "Order {$orderNumber} deleted.");
    }
}
