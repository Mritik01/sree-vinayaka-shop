<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\ShopSetting;
use App\Notifications\OrderCancelled;
use App\Services\ActivityLogger;
use App\Services\RefundService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function show(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items.product', 'coupon', 'fees', 'rider', 'riderRating'])->findOrFail($orderId);

        return view('order-tracking', [
            'order' => $order,
            'orderForJs' => $this->payload($order),
            'justPlaced' => $request->boolean('placed'),
            // set when arriving from the notification bell's "admin replied" link, so the
            // customer lands straight in the conversation instead of having to find the button
            'autoOpenChat' => $request->boolean('chat'),
        ]);
    }

    // polled by the tracking page (every 5s) so status changes the admin makes show up live —
    // also now the channel an admin's item removal reaches the customer through, since items/
    // totals are part of payload() below
    public function status(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['rider', 'riderRating', 'items.product', 'coupon', 'fees'])->findOrFail($orderId);

        return response()->json(['ok' => true, 'order' => $this->payload($order)]);
    }

    // fetched by the account page's "My Orders" tab so an order's full detail renders inline
    // instead of navigating to a separate page — see resources/js/app.js: accountPage().viewOrder()
    public function partial(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items.product', 'coupon', 'fees', 'rider', 'riderRating'])->findOrFail($orderId);

        return view('partials.order-detail', [
            'order' => $order,
            'orderForJs' => $this->payload($order),
            'justPlaced' => false,
            'autoOpenChat' => false,
        ]);
    }

    public function updateNote(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if (in_array($order->status, ['out_for_delivery', 'delivered', 'cancelled'])) {
            return response()->json([
                'ok' => false,
                'message' => $order->status === 'out_for_delivery'
                    ? "Your order is already on its way, so its note can no longer be changed."
                    : 'This order is already closed, so its note can no longer be changed.',
            ], 422);
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $note = trim($data['note'] ?? '') ?: null;

        // a new/changed note always needs fresh eyes from the shop — reset any earlier decision.
        // The customer-side Save button is already disabled unless the text actually changed, so
        // this always represents a genuine edit, never a no-op resubmit of the same text.
        $order->update([
            'customer_note' => $note,
            'note_status' => $note ? 'pending' : null,
            'note_decision_message' => null,
            'note_decided_by_admin_id' => null,
            'note_decided_at' => null,
        ]);

        return response()->json([
            'ok' => true,
            'note' => $order->customer_note,
            'note_status' => $order->note_status,
            'note_decision_message' => $order->note_decision_message,
        ]);
    }

    public function cancel(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if (!$order->isCustomerCancellable()) {
            $message = match (true) {
                $order->status === 'out_for_delivery' => "Your order is already on its way, so it can't be cancelled now. You can refuse it at the door if needed.",
                in_array($order->status, Order::CUSTOMER_CANCELLABLE_STATUSES) =>
                    'The '.Order::CUSTOMER_CANCEL_WINDOW_MINUTES.'-minute cancellation window has passed — the shop may already be preparing your order. Please call '.(ShopSetting::current()->businessPhoneDisplay() ?? 'the shop').' if you still need to cancel.',
                default => 'This order can no longer be cancelled.',
            };

            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        // a customer cancelling their own order intentionally does NOT set the COD restriction —
        // that penalty only applies when the ADMIN cancels (customer didn't accept delivery)
        $order->forceFill([
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancellation_reason' => trim($data['reason'] ?? '') ?: null,
            'cancelled_at' => now(),
        ])->save();

        ActivityLogger::log('order_cancelled', "Order {$order->orderNumber()} cancelled by customer");

        // already paid online? refund it in full automatically — the customer shouldn't have
        // to ask for money the shop never actually earned
        RefundService::autoRefundOnCancel($order);

        OrderCancelled::notifyAdmins($order);

        return response()->json(['ok' => true, 'order' => $this->payload($order)]);
    }

    public function invoice(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->with(['items', 'coupon', 'fees'])->findOrFail($orderId);

        $pdf = Pdf::loadView('invoice', ['order' => $order]);

        return $pdf->download("invoice-{$order->orderNumber()}.pdf");
    }

    private function payload(Order $order): array
    {
        // drives the "⭐ Rate Products" popup — gated on delivered status so this query never
        // runs on the other statuses' every-5s polls (see partials/product-rating-popup.blade.php
        // and window.productRatingPopup() in app.js). Ratings are product-scoped (the existing
        // `reviews` table, unique per user+product, reused as-is — not order-scoped), so "already
        // rated" means "this customer has rated this product before," in any order.
        $ratableProductIds = collect();
        $existingReviews = collect();
        if ($order->status === 'delivered') {
            $ratableProductIds = $order->items
                ->filter(fn ($item) => $item->removed_at === null && $item->product_id !== null)
                ->pluck('product_id')->unique()->values();
            $existingReviews = $ratableProductIds->isNotEmpty()
                ? Review::where('user_id', $order->user_id)->whereIn('product_id', $ratableProductIds)->get()->keyBy('product_id')
                : collect();
        }

        return [
            'id' => $order->id,
            'order_number' => $order->orderNumber(),
            'status' => $order->status,
            'eta_minutes' => $order->eta_minutes,
            // unix ms the delivery was promised for; the client counts down against it
            'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                : null,
            'rider_id' => $order->rider_id,
            'rider_name' => $order->rider?->name,
            'rider_phone' => $order->rider?->phone,
            'rider_photo_url' => $order->rider?->photo_path ? asset($order->rider->photo_path) : null,
            // drives the rider-rating popup/reminder — see orderTrackingPage() in app.js and
            // partials/rider-rating-popup.blade.php
            'needs_rating' => $order->status === 'delivered' && $order->rider_id !== null && $order->riderRating === null,
            'ratable_product_count' => $ratableProductIds->count(),
            'all_products_rated' => $ratableProductIds->isNotEmpty() && $ratableProductIds->every(fn ($id) => $existingReviews->has($id)),
            'customer_note' => $order->customer_note,
            // drives the note-decision popup/status chip — see orderTrackingPage() in app.js and
            // partials/note-decision-popup.blade.php. note_decided_at (ISO string) is the
            // per-decision fingerprint the client uses to show the popup exactly once per decision.
            'note_status' => $order->note_status,
            'note_decision_message' => $order->note_decision_message,
            'note_decided_at' => $order->note_decided_at?->toIso8601String(),
            'cancelled_by' => $order->cancelled_by,
            'can_cancel' => $order->isCustomerCancellable(),
            // unix ms the self-cancel window closes; the client counts down against it so the
            // button can disappear the instant it expires, not just on the next 5s poll
            'cancel_window_ends_at' => $order->cancelWindowEndsAt(),
            'placed_at' => $order->created_at->format('d M, h:i A'),
            'confirmed_at' => $order->confirmed_at?->format('h:i A'),
            'out_for_delivery_at' => $order->out_for_delivery_at?->format('h:i A'),
            'delivered_at' => $order->delivered_at?->format('h:i A'),
            'cancelled_at' => $order->cancelled_at?->format('h:i A'),
            // items + bill folded into this same polled payload so an admin's item removal (see
            // Admin\OrderController::removeItem) shows up on the tracking page within one 5s
            // poll tick — no separate endpoint/mechanism needed. Includes removed items too (the
            // client renders them struck-through) so nothing silently disappears from history.
            'items' => $order->items->map(function ($item) use ($existingReviews) {
                $ratable = $item->removed_at === null && $item->product_id !== null;
                $existing = $ratable ? $existingReviews->get($item->product_id) : null;

                return [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'image_url' => $item->product ? asset($item->product->image) : null,
                    'portion_label' => $item->portionLabel(),
                    'quantity' => $item->quantity,
                    'price' => (int) $item->product_price,
                    'line_total' => (int) $item->line_total,
                    'is_gift' => $item->is_gift,
                    'removed' => $item->removed_at !== null,
                    'removal_reason' => $item->removal_reason,
                    'removal_reason_label' => $item->removalReasonLabel(),
                    'removal_reason_note' => $item->removal_reason_note,
                    // drives the "🎉 Your order was updated!" card — see the note above about
                    // Admin\OrderController::addItems() and maybeShowOrderUpdated() in app.js
                    'added' => $item->added_at !== null,
                    // drives the "⭐ Rate Products" popup — see productRatingPopup() in app.js
                    'product_id' => $ratable ? $item->product_id : null,
                    'product_slug' => $ratable ? $item->product?->slug : null,
                    'already_rated' => $ratable ? $existing !== null : null,
                    'existing_rating' => $existing?->rating,
                    'existing_comment' => $existing?->comment,
                ];
            })->values(),
            'subtotal' => (int) $order->subtotal,
            'discount_amount' => (int) $order->discount_amount,
            'coupon_code' => $order->coupon->code ?? null,
            'fees' => $order->fees->map(fn ($fee) => ['label' => $fee->label, 'amount' => (int) $fee->amount])->values(),
            'total' => (int) $order->total,
            'payment_method' => $order->payment_method,
            'amount_paid' => $order->amount_paid !== null ? (int) $order->amount_paid : null,
            // what the rider still needs to collect in cash — the full total for COD (unchanged
            // behaviour), 0 for a settled online payment, or just the difference when an admin
            // added items to an order that was already paid — see Order::balanceDueOnDelivery()
            'balance_due' => $order->balanceDueOnDelivery(),
            // ISO timestamp of the most recent admin-added item, used as the per-event fingerprint
            // for maybeShowOrderUpdated() in app.js (mirrors note_decided_at's role above) — null
            // when nothing has ever been added to this order
            'items_added_at' => $order->items->pluck('added_at')->filter()->max()?->toIso8601String(),
        ];
    }
}
