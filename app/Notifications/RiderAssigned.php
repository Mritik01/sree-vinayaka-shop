<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Rider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RiderAssigned extends Notification
{
    use Queueable;

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
