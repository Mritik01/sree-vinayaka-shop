<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// queueable so sending this never blocks the request that triggered it — see AdminMessage.php.
// SerializesModels matters here specifically: the Order/OrderItem constructor properties need
// to serialize as lightweight model references (not full PHP object serialization) once this
// actually goes through a real queue driver instead of sync.
class ItemsRemovedFromOrder extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

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
