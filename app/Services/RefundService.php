<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RefundService
{
    /**
     * Refunds up to $amount (defaults to whatever's left refundable) of a Razorpay-paid order
     * through the real Razorpay API. Returns ['ok' => bool, 'message' => string].
     *
     * Uses Laravel's own Log facade rather than ActivityLogger — this runs from both real HTTP
     * requests (admin panel, customer cancelling) AND a pure CLI context (the stale-order
     * auto-cancel scheduled command), and ActivityLogger assumes an HTTP session always exists
     * (see OrderAutoCancelService for the same constraint).
     */
    public static function refund(Order $order, ?int $amount = null): array
    {
        if (!$order->isRefundable()) {
            return ['ok' => false, 'message' => "Order {$order->orderNumber()} has nothing left to refund."];
        }

        $amount = $amount !== null ? min($amount, $order->refundableAmount()) : $order->refundableAmount();

        if ($amount < 1) {
            return ['ok' => false, 'message' => "Order {$order->orderNumber()} has nothing left to refund."];
        }

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $payment = $api->payment->fetch($order->razorpay_payment_id);
            $refund = $payment->refund(['amount' => $amount * 100]);
        } catch (\Throwable $e) {
            Log::warning("Refund failed for order {$order->orderNumber()}: {$e->getMessage()}");

            return ['ok' => false, 'message' => "Refund failed: {$e->getMessage()}"];
        }

        $refundedTotal = $order->refunded_amount + $amount;

        $order->forceFill([
            'refunded_amount' => $refundedTotal,
            // compared against amount_paid (what was truly captured), not the possibly-since-
            // shrunk `total` — see Order::refundableAmount()
            'refund_status' => $refundedTotal >= ($order->amount_paid ?? $order->total) ? 'full' : 'partial',
            'razorpay_refund_id' => $refund->id,
            'refunded_at' => now(),
        ])->save();

        $message = '₹'.number_format($amount)." refunded for order {$order->orderNumber()} (Razorpay refund {$refund->id}).";

        // a fully refunded order has nothing left to fulfill — if it's still in progress (not
        // already delivered or cancelled), close it out automatically rather than leaving it
        // sitting there for someone to dispatch/deliver for free. A partial refund never does
        // this — that's often a goodwill gesture (e.g. discounting a damaged item) where the
        // order is still meant to go out. Doesn't touch an order the caller already cancelled
        // (e.g. autoRefundOnCancel() below runs on an order that's already 'cancelled').
        if ($order->refund_status === 'full' && !in_array($order->status, ['delivered', 'cancelled'])) {
            $order->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => 'admin',
                'cancelled_at' => now(),
            ])->save();

            $message .= ' Order was automatically cancelled since it was refunded in full.';
        }

        Log::info($message);

        return ['ok' => true, 'message' => $message];
    }

    /**
     * Called wherever an order transitions to 'cancelled' (customer-initiated, admin-initiated,
     * or the system's stale-order auto-cancel) — if it was paid online and nothing's been
     * refunded yet, refunds it in full automatically. Never throws: a failure here must not
     * block the cancellation itself, it just leaves the order refundable for a manual retry
     * later via the "Refund" section on the admin order page.
     */
    public static function autoRefundOnCancel(Order $order): void
    {
        if (!$order->isRefundable()) {
            return;
        }

        static::refund($order);
    }
}
