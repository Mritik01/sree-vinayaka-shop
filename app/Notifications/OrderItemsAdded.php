<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class OrderItemsAdded extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Models\OrderItem>  $addedItems
     */
    public function __construct(
        public Order $order,
        public Collection $addedItems,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // "Nankhatai (500g) ×2, Kaju Katli ×1" — same phrasing style the customer sees on the card
        $summary = $this->addedItems->map(function ($item) {
            $label = $item->product_name;
            if ($item->portionLabel()) {
                $label .= ' ('.$item->portionLabel().')';
            }
            return $label.' ×'.$item->quantity;
        })->implode(', ');

        $message = __('At your request, we added :items to :order — your total is now ₹:total.', [
            'items' => $summary,
            'order' => $this->order->orderNumber(),
            'total' => number_format($this->order->total),
        ]);

        // for an online-paid order the extra is collected in cash on delivery — tell the customer
        if ($this->order->hasPostPaymentBalance()) {
            $message .= ' '.__('Please keep ₹:balance ready to pay on delivery.', [
                'balance' => number_format($this->order->balanceDueOnDelivery()),
            ]);
        }

        return [
            'title' => '🎉 '.__('Your order was updated'),
            'message' => $message,
            'url' => route('orders.show', $this->order->id),
        ];
    }
}
