<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // a rider only ever needs the active delivery queue — not pending (not confirmed by the
    // shop yet) and not the terminal delivered/cancelled ones
    private const QUEUE_STATUSES = ['confirmed', 'out_for_delivery'];

    // narrower than Order::STATUSES — a rider can move an order forward one step at a time,
    // never confirm a pending order or cancel one (that stays an admin-only action)
    private const RIDER_STATUSES = ['out_for_delivery', 'delivered'];

    public function index(Request $request)
    {
        return view('rider.orders.index', ['orders' => $this->queueForJs()]);
    }

    // polled by the deliveries list every few seconds so a freshly-assigned order (or one an
    // admin just unassigned/cancelled) appears/disappears without the rider reloading —
    // see resources/js/rider.js: riderOrdersPage()
    public function poll(Request $request)
    {
        return response()->json(['ok' => true, 'orders' => $this->queueForJs()]);
    }

    // a rider only ever sees orders an admin has explicitly assigned to them — never the
    // full open queue (see Admin\OrderController::assignRider)
    private function queueForJs(): array
    {
        $orders = Order::with('items')
            ->where('rider_id', Auth::guard('rider')->id())
            ->whereIn('status', self::QUEUE_STATUSES)
            ->oldest()
            ->get();

        return $orders->map(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->orderNumber(),
            'customer_name' => $order->customer_name,
            'delivery_address' => $order->delivery_address,
            'items_count' => $order->items->count(),
            'total' => (int) $order->total,
            'status' => $order->status,
            'payment_method' => strtoupper($order->payment_method ?? 'COD'),
            'payment_status' => $order->payment_status,
        ])->values()->all();
    }

    public function show(Order $order)
    {
        abort_unless($order->rider_id === Auth::guard('rider')->id(), 404);

        $order->load('items.product');

        return view('rider.orders.show', ['order' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless($order->rider_id === Auth::guard('rider')->id(), 404);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::RIDER_STATUSES),
        ]);

        if (!in_array($order->status, self::QUEUE_STATUSES)) {
            return back()->with('status', "Order {$order->orderNumber()} is no longer in the active delivery queue.");
        }

        // one step at a time — confirmed -> out_for_delivery -> delivered, never skip ahead
        $allowedNext = ['confirmed' => 'out_for_delivery', 'out_for_delivery' => 'delivered'];
        if ($allowedNext[$order->status] !== $data['status']) {
            return back()->with('status', "Order {$order->orderNumber()} can't jump to that status from here.");
        }

        $wasDelivered = $order->status === 'delivered';

        $order->status = $data['status'];
        $order->save();
        $order->stampStatusTimestamp();

        if (!$wasDelivered && $order->status === 'delivered' && $order->user) {
            RewardService::recordDelivery($order->user);
        }

        return redirect()->route('rider.orders.show', $order)->with('status', 'Order '.$order->orderNumber().' marked '.str_replace('_', ' ', $order->status).'.');
    }

    public function uploadPhoto(Request $request, Order $order)
    {
        abort_unless($order->rider_id === Auth::guard('rider')->id(), 404);

        $request->validate(['photo' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:8192']);

        // replace, don't accumulate — only ever one proof-of-delivery photo per order
        if ($order->delivery_photo_path) {
            @unlink(public_path($order->delivery_photo_path));
        }

        $file = $request->file('photo');
        // extension from the detected content type, never the client-supplied original filename
        // (see SupportMessage::storeImage for why that matters — same fix applied here)
        $filename = 'order-'.$order->id.'-'.Str::random(8).'.'.($file->extension() ?: 'jpg');
        $file->move(public_path('images/delivery-photos'), $filename);

        $order->delivery_photo_path = 'images/delivery-photos/'.$filename;
        $order->delivery_photo_uploaded_at = now();
        $order->save();

        return redirect()->route('rider.orders.show', $order)->with('status', 'Photo attached to order '.$order->orderNumber().'.');
    }
}
