<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderAutoCancelService
{
    // "delivery not completed within 1.5 hours of the order being placed" — a safety net so an
    // order can never sit unfulfilled (or unactioned) forever
    private const STALE_MINUTES = 90;

    /**
     * Cancels every order that's still open (pending/confirmed/out for delivery) but was placed
     * more than STALE_MINUTES ago. Returns the orders that were just cancelled by this call.
     */
    public static function cancelStaleOrders(): Collection
    {
        $cutoff = now()->subMinutes(self::STALE_MINUTES);

        $staleOrders = Order::whereIn('status', ['pending', 'confirmed', 'out_for_delivery'])
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($staleOrders as $order) {
            // never the customer's fault — the shop failed to fulfill in time, so this must NOT
            // trigger the COD-block restriction that a customer- or admin-initiated cancel can
            $order->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => 'system',
                'cancelled_at' => now(),
            ])->save();

            // this runs from both an HTTP context (piggybacked on the admin poll) and a pure CLI
            // context (the scheduled command) — Laravel's own logger works in both; ActivityLogger
            // does not, since it assumes an HTTP session always exists
            Log::warning("Order #{$order->id} auto-cancelled — not delivered within ".self::STALE_MINUTES.' min of being placed');
        }

        return $staleOrders;
    }
}
