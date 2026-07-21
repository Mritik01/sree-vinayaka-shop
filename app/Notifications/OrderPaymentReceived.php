<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// database-only, unlike OrderCancelled — this fires on every single COD delivery (potentially
// dozens a day), so a real email per order would be noise rather than signal. The in-app bell
// plus the live toast (see admin.js/adminNotifier) are enough for an event this routine.
class OrderPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    // same defensive per-admin loop as OrderCancelled::notifyAdmins() — one admin's broken
    // notification setup must never take the others down with it
    public static function notifyAdmins(Order $order): void
    {
        foreach (Admin::all() as $admin) {
            try {
                $admin->notify(new self($order));
            } catch (\Throwable $e) {
                Log::error("OrderPaymentReceived notification failed for admin {$admin->id}, order {$order->id}", ['message' => $e->getMessage()]);
            }
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $order = $this->order;

        return [
            'title' => "💰 Payment Received — {$order->orderNumber()}",
            'message' => "{$order->customer_name} ({$order->customer_phone}) — ₹".number_format($order->amount_paid)
                .' collected on delivery'.($order->rider ? " by {$order->rider->name}" : '').'.',
            'url' => route('admin.orders.show', $order->id),
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'order_value' => $order->total,
            'rider_name' => $order->rider->name ?? null,
            'paid_at' => optional($order->paid_at)->toIso8601String(),
        ];
    }
}
