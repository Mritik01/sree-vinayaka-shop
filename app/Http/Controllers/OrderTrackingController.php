<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function show(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items', 'coupon'])->findOrFail($orderId);

        return view('order-tracking', [
            'order' => $order,
            'orderForJs' => $this->payload($order),
            'justPlaced' => $request->boolean('placed'),
        ]);
    }

    // polled by the tracking page so status changes the admin makes show up live
    public function status(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        return response()->json(['ok' => true, 'order' => $this->payload($order)]);
    }

    // fetched by the account page's "My Orders" tab so an order's full detail renders inline
    // instead of navigating to a separate page — see resources/js/app.js: accountPage().viewOrder()
    public function partial(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items', 'coupon'])->findOrFail($orderId);

        return view('partials.order-detail', [
            'order' => $order,
            'orderForJs' => $this->payload($order),
            'justPlaced' => false,
        ]);
    }

    public function updateNote(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'ok' => false,
                'message' => 'This order is already closed, so its note can no longer be changed.',
            ], 422);
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $order->update(['customer_note' => trim($data['note'] ?? '') ?: null]);

        return response()->json(['ok' => true, 'note' => $order->customer_note]);
    }

    public function cancel(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if (!$order->isCustomerCancellable()) {
            return response()->json([
                'ok' => false,
                'message' => $order->status === 'out_for_delivery'
                    ? "Your order is already on its way, so it can't be cancelled now. You can refuse it at the door if needed."
                    : 'This order can no longer be cancelled.',
            ], 422);
        }

        // a customer cancelling their own order intentionally does NOT set the COD restriction —
        // that penalty only applies when the ADMIN cancels (customer didn't accept delivery)
        $order->forceFill([
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancelled_at' => now(),
        ])->save();

        ActivityLogger::log('order_cancelled', "Order #{$order->id} cancelled by customer");

        return response()->json(['ok' => true, 'order' => $this->payload($order)]);
    }

    public function invoice(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items', 'coupon'])->findOrFail($orderId);

        $pdf = Pdf::loadView('invoice', ['order' => $order]);

        return $pdf->download("invoice-order-{$order->id}.pdf");
    }

    private function payload(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'eta_minutes' => $order->eta_minutes,
            // unix ms the delivery was promised for; the client counts down against it
            'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                : null,
            'customer_note' => $order->customer_note,
            'cancelled_by' => $order->cancelled_by,
            'can_cancel' => $order->isCustomerCancellable(),
            'placed_at' => $order->created_at->format('d M, h:i A'),
            'confirmed_at' => $order->confirmed_at?->format('h:i A'),
            'out_for_delivery_at' => $order->out_for_delivery_at?->format('h:i A'),
            'delivered_at' => $order->delivered_at?->format('h:i A'),
            'cancelled_at' => $order->cancelled_at?->format('h:i A'),
        ];
    }
}
