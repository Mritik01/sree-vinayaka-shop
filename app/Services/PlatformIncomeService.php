<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PlatformIncomeRecord;
use Illuminate\Database\QueryException;

// the single place platform income is ever calculated or written — called exactly once, from
// the two places an order can transition into 'delivered' (Admin\OrderController::updateStatus
// and Rider\OrderController::updateStatus), each already gated on "!$wasDelivered && status ===
// 'delivered'" so this only ever runs on the FIRST time an order reaches that status. Since
// 'delivered' is a terminal status (no code path ever moves an order back out of it — see
// Order::STATUSES and the terminal-status guard in both updateStatus() methods), a cancelled or
// refunded order can never reach here after the fact, satisfying "cancelled/refunded orders
// don't generate income" by construction rather than an extra check.
class PlatformIncomeService
{
    public const FIXED_COMMISSION = 15;
    public const DELIVERY_CHARGE_SHARE = 0.5;

    public static function recordForDelivery(Order $order): void
    {
        // belt-and-suspenders: the unique index on order_id is the real guarantee (see try/catch
        // below), this just avoids a wasted insert attempt on the common non-race path
        if (PlatformIncomeRecord::where('order_id', $order->id)->exists()) {
            return;
        }

        $deliveryCharge = (int) ($order->fees()->where('key', 'delivery')->value('amount') ?? 0);
        $deliveryChargeIncome = (int) round($deliveryCharge * self::DELIVERY_CHARGE_SHARE);

        try {
            PlatformIncomeRecord::create([
                'order_id' => $order->id,
                'rider_id' => $order->rider_id,
                'customer_name' => $order->customer_name,
                'order_amount' => $order->total,
                'delivery_charge' => $deliveryCharge,
                'fixed_commission' => self::FIXED_COMMISSION,
                'delivery_charge_income' => $deliveryChargeIncome,
                'total_income' => self::FIXED_COMMISSION + $deliveryChargeIncome,
                'delivered_at' => $order->delivered_at ?? now(),
            ]);
        } catch (QueryException $e) {
            // two requests racing to record the same order's delivery (e.g. an admin and a
            // rider both hitting updateStatus around the same moment) — the unique(order_id)
            // constraint rejected the loser, and the winner's row already has everything needed
        }
    }
}
