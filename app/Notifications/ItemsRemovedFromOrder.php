<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItemsRemovedFromOrder extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderItem $item,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => '😔 '.__('An item was removed from your order'),
            'message' => __(':item was unavailable and has been removed from :order — your total is now ₹:total.', [
                'item' => $this->item->product_name,
                'order' => $this->order->orderNumber(),
                'total' => number_format($this->order->total),
            ]),
            'url' => route('orders.show', $this->order->id),
        ];
    }
}
