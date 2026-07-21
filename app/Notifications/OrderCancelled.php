<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// queueable so sending this never blocks the request that triggered it — see AdminMessage.php.
// This is the one notification with a real mail send (to every admin with an email set), so
// it's the biggest beneficiary: today it blocks the cancelling request on N synchronous SMTP
// round-trips (customer cancel, admin cancel, and the 5s auto-cancel sweep all trigger this).
class OrderCancelled extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    /**
     * The only entry point callers should use — the in-app notification (the reliable, always-on
     * part of this feature) must never be lost because the optional email channel couldn't reach
     * an SMTP server. Same defensive shape as TwoFactorOtpService's send(): try the extra channel,
     * log and move on if it fails, never let it take down the request that got us here.
     *
     * Notified one admin at a time (not a single batched Notification::send() call) so one
     * admin's broken/unreachable email can't take the others down with it — `via()` puts
     * 'database' first, so that part is already persisted before 'mail' is even attempted.
     */
    public static function notifyAdmins(Order $order): void
    {
        foreach (Admin::all() as $admin) {
            try {
                $admin->notify(new self($order));
            } catch (\Throwable $e) {
                Log::error("OrderCancelled notification failed for admin {$admin->id}, order {$order->id}", ['message' => $e->getMessage()]);
            }
        }
    }

    // always lands in the admin's in-app notification center; also emails them if (and only
    // if) they've bothered to set an email address on their admin account — see Admin Accounts
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $order = $this->order;

        return [
            'title' => "Order {$order->orderNumber()} Cancelled",
            'message' => $this->summaryLine(),
            'url' => route('admin.orders.show', $order->id),
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'order_value' => $order->total,
            'cancelled_at' => optional($order->cancelled_at)->toIso8601String(),
            'cancelled_by' => $order->cancelled_by,
            'cancellation_reason' => $order->cancellation_reason,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        $mail = (new MailMessage)
            ->subject("Order {$order->orderNumber()} Cancelled")
            ->greeting('An order was just cancelled')
            ->line("Order: {$order->orderNumber()}")
            ->line("Customer: {$order->customer_name} ({$order->customer_phone})")
            ->line('Order Value: ₹'.number_format($order->total))
            ->line('Cancelled At: '.optional($order->cancelled_at)->format('d M Y, h:i A'));

        if ($order->cancellation_reason) {
            $mail->line("Reason: {$order->cancellation_reason}");
        }

        return $mail->action('View Order', route('admin.orders.show', $order->id));
    }

    private function summaryLine(): string
    {
        $order = $this->order;

        $line = "{$order->customer_name} ({$order->customer_phone}) — ₹".number_format($order->total)
            .' — cancelled '.optional($order->cancelled_at)->format('d M, h:i A');

        return $order->cancellation_reason
            ? $line."\nReason: {$order->cancellation_reason}"
            : $line;
    }
}
