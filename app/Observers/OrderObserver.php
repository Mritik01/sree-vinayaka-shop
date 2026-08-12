<?php

namespace App\Observers;

use App\Jobs\SendAiSensyMessage;
use App\Models\Order;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

// the single hook for every WhatsApp send tied to an order's lifecycle (see AiSensyService). Only
// listens for updated() — no created() hook, since the shop only wants one message sent when an
// order is confirmed, not a separate one at checkout too. Deliberately independent of
// Order::stampStatusTimestamp() — no shared state, purely an additional listener — and
// deliberately not routed through Laravel's Notification system (App\Notifications\*), since
// those are the in-app/admin-email notifications, a separate concern.
class OrderObserver
{
    private const STATUS_EVENT_MAP = [
        'confirmed' => 'order_confirmed',
        'out_for_delivery' => 'out_for_delivery',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ];

    // covers every status-changing call site (Admin\OrderController::updateStatus(),
    // Rider\OrderController::updateStatus(), OrderTrackingController::cancel(),
    // OrderAutoCancelService::cancelStaleOrders()) without touching any of them — all four end up
    // calling Order::save(), which always fires this event regardless of whether the caller used
    // direct assignment or forceFill().
    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status')) {
            return; // guards against firing on unrelated saves — recalculateTotals(),
                    // assignRider(), delivery-photo uploads, note responses, etc.
        }

        $eventKey = self::STATUS_EVENT_MAP[$order->status] ?? null;

        if ($eventKey === null) {
            return;
        }

        $this->dispatch($eventKey, $order);
    }

    private function dispatch(string $eventKey, Order $order): void
    {
        $phone = PhoneNumber::normalize((string) $order->customer_phone);

        if (!PhoneNumber::isValidIndianMobile($phone)) {
            Log::warning("AiSensy dispatch skipped for order #{$order->id}: invalid phone.");

            return;
        }

        SendAiSensyMessage::dispatch($eventKey, $phone, $this->templateParamsFor($eventKey, $order), $order->customer_name);
    }

    // positional params for each event's approved template, confirmed against the actual template
    // bodies in AiSensy — adjust here if a template changes. Every confirmed template so far bakes
    // a literal "#" in before the order number placeholder, so $orderNumber below always strips
    // Order::orderNumber()'s own leading "#" to avoid doubling it (e.g. "##MKB-...").
    private function templateParamsFor(string $eventKey, Order $order): array
    {
        $orderNumber = ltrim($order->orderNumber(), '#');

        return match ($eventKey) {
            'order_confirmed' => [
                $order->customer_name,
                $orderNumber,
                (string) $order->total,
            ],
            'out_for_delivery' => [
                $order->customer_name,
                $orderNumber,
            ],
            // just name + order id — no amount placeholder in this template at all (confirmed
            // against the real template body)
            'delivered' => [
                $order->customer_name,
                $orderNumber,
            ],
            // {{3}} is the order amount, not the cancellation reason — the template itself
            // prefixes it with "₹" (confirmed against the real template body: sending the reason
            // there rendered as "₹Not delivered within 90 minutes...")
            'cancelled' => [
                $order->customer_name,
                $orderNumber,
                (string) $order->total,
            ],
            default => [],
        };
    }
}
