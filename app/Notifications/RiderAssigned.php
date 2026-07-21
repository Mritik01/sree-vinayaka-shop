<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Rider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// queueable so sending this never blocks the request that triggered it — see AdminMessage.php
class RiderAssigned extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Rider $rider,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => '🛵 '.__('Delivery partner assigned!'),
            'message' => __(':name will be delivering your order :order_number.', [
                'name' => $this->rider->name,
                'order_number' => $this->order->orderNumber(),
            ]),
            'url' => route('orders.show', $this->order->id),
        ];
    }
}
