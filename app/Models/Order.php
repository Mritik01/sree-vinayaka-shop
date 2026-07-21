<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];

    // statuses the customer is still allowed to cancel from — once the rider leaves, it's too late
    public const CUSTOMER_CANCELLABLE_STATUSES = ['pending', 'confirmed'];

    // a customer can only self-cancel within this many minutes of placing the order — after
    // that, the shop may already be preparing it, so a call to the shop is required instead
    public const CUSTOMER_CANCEL_WINDOW_MINUTES = 2;

    protected $fillable = [
        'user_id',
        'rider_id',
        'coupon_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'latitude',
        'longitude',
        'distance_km',
        'subtotal',
        'discount_amount',
        'total',
        'amount_paid',
        'status',
        'eta_minutes',
        'payment_method',
        'payment_status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'paid_at',
        'refund_status',
        'refunded_amount',
        'razorpay_refund_id',
        'refunded_at',
        'customer_note',
        'note_status',
        'note_decision_message',
        'note_decided_by_admin_id',
        'note_decided_at',
        'is_gift_order',
        'cancelled_by',
        'cancellation_reason',
        'confirmed_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at',
        'delivery_photo_path',
        'delivery_photo_uploaded_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'delivery_photo_uploaded_at' => 'datetime',
        'note_decided_at' => 'datetime',
        'is_gift_order' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function noteDecidedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'note_decided_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // items() minus anything an admin has removed for unavailability — the invoice, the admin's
    // main packing list, and the customer's bill/total all read through this, never items()
    // directly, so a removed item can never silently reappear in a total or a printed document
    public function activeItems(): HasMany
    {
        return $this->items()->whereNull('removed_at');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(OrderFee::class);
    }

    public function supportMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function latestSupportMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function riderRating(): HasOne
    {
        return $this->hasOne(RiderRating::class);
    }

    public function isCustomerCancellable(): bool
    {
        return in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES)
            && $this->created_at->addMinutes(self::CUSTOMER_CANCEL_WINDOW_MINUTES)->isFuture();
    }

    // unix ms the customer's self-cancel window closes — null once it's not relevant anymore
    // (order already moved past a cancellable status), so the client can't show a stale timer
    public function cancelWindowEndsAt(): ?int
    {
        if (!in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES)) {
            return null;
        }

        return $this->created_at->addMinutes(self::CUSTOMER_CANCEL_WINDOW_MINUTES)->valueOf();
    }

    // how much of what was actually captured hasn't been refunded yet — the max a new refund can
    // ask for. Deliberately based on amount_paid (frozen the instant Razorpay confirmed payment),
    // not `total` — Order::recalculateTotals() can shrink `total` after an item removal, and the
    // shop still owes the customer the full difference from what was genuinely charged.
    public function refundableAmount(): int
    {
        return max(0, ($this->amount_paid ?? $this->total) - $this->refunded_amount);
    }

    // convenience for the admin order page's "already paid ₹X, new total is ₹Y" note — 0 unless
    // an item removal has actually left the order owing the customer money back
    public function unrefundedGapFromRemovedItems(): int
    {
        if ($this->payment_method !== 'razorpay' || $this->payment_status !== 'paid' || $this->amount_paid === null) {
            return 0;
        }

        return max(0, $this->amount_paid - $this->total - $this->refunded_amount);
    }

    // true only once a COD order's cash has actually been handed over — the only place this ever
    // becomes true is Rider\OrderController::markPaymentReceived(), since RazorpayController only
    // ever writes payment_status for payment_method === 'razorpay'
    public function codPaymentReceived(): bool
    {
        return $this->payment_method !== 'razorpay' && $this->payment_status === 'paid';
    }

    public function isRefundable(): bool
    {
        return $this->payment_method === 'razorpay'
            && $this->payment_status === 'paid'
            && $this->razorpay_payment_id
            && $this->refundableAmount() > 0;
    }

    // Cash the rider still needs to collect on delivery. One uniform formula for every case:
    //   COD (amount_paid NULL)                     → total - 0        = the full total (unchanged)
    //   Razorpay-paid, untouched                   → total - total    = 0 (nothing to collect)
    //   Razorpay-paid + admin added items later    → newTotal - paid  = just the difference
    // amount_paid stays frozen at Razorpay capture, so this never disturbs the refund stack —
    // when total rises past amount_paid, unrefundedGapFromRemovedItems() simply returns 0.
    public function balanceDueOnDelivery(): int
    {
        return max(0, $this->total - (int) ($this->amount_paid ?? 0));
    }

    // true only for an already-paid online order that now owes more because items were added
    // after payment — gates the "collect the difference on delivery" UI without touching the
    // ordinary paid-and-settled case (which reads balanceDueOnDelivery() === 0)
    public function hasPostPaymentBalance(): bool
    {
        return $this->payment_method === 'razorpay'
            && $this->payment_status === 'paid'
            && $this->total > (int) ($this->amount_paid ?? 0);
    }

    /**
     * A friendly, shop-branded stand-in for the Razorpay order id (e.g. "order_TD25zoP6lBfe1W")
     * so admins never have to read/copy Razorpay's own opaque id. Derived straight from the
     * order's own primary key — no separate column to keep in sync, no collision handling
     * needed, and it works retroactively for every past order too.
     */
    public function transactionId(): string
    {
        return 'TXID'.str_pad((string) $this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Reverses transactionId() so admin search boxes can accept "TXID00000047" and resolve it
     * back to order #47. Returns null for anything that isn't a well-formed transaction id.
     */
    public static function idFromTransactionId(string $value): ?int
    {
        return preg_match('/^TXID0*(\d+)$/i', trim($value), $m) ? (int) $m[1] : null;
    }

    /**
     * The customer/admin-facing order number (e.g. "#MKB-2026-000001") shown everywhere in place
     * of the raw numeric id. Derived from the order's own id and the year it was placed — no
     * separate column to keep in sync, and it works retroactively for every past order too.
     */
    public function orderNumber(): string
    {
        return '#MKB-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Reverses orderNumber() so admin search boxes can accept "#MKB-2026-000001" (with or
     * without the leading #) and resolve it back to order #1. The year segment is ignored (an
     * order's id is already unique on its own) so a search still matches even if someone
     * mistypes the year.
     */
    public static function idFromOrderNumber(string $value): ?int
    {
        return preg_match('/^#?MKB-\d{4}-0*(\d+)$/i', trim($value), $m) ? (int) $m[1] : null;
    }

    /**
     * Re-derives subtotal/discount/total from whatever items are still active, after an admin
     * removes one for unavailability (see Admin\OrderController::removeItem()). Only the
     * subtotal-dependent 'delivery' OrderFee row is recomputed — 'rain'/'high_demand' are flat
     * surcharges unrelated to order value — and it's recomputed against *today's* ShopSetting
     * rules (not whatever was true at checkout), per this feature's own spec.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->activeItems()->sum('line_total');
        $discount = ($this->coupon_id && $this->coupon) ? $this->coupon->discountFor($subtotal) : 0;

        $deliveryFee = $this->fees()->where('key', 'delivery')->first();
        if ($deliveryFee) {
            $deliveryFee->update(['amount' => ShopSetting::current()->deliveryFeeFor($subtotal)]);
        }

        $this->subtotal = $subtotal;
        $this->discount_amount = $discount;
        $this->total = max(0, $subtotal - $discount + (int) $this->fees()->sum('amount'));
        $this->save();
    }

    /**
     * Stamp the matching *_at timestamp for a status transition (only the first
     * time the order enters that status, so re-setting a status never rewrites history).
     */
    public function stampStatusTimestamp(): void
    {
        $column = match ($this->status) {
            'confirmed' => 'confirmed_at',
            'out_for_delivery' => 'out_for_delivery_at',
            'delivered' => 'delivered_at',
            'cancelled' => 'cancelled_at',
            default => null,
        };

        if ($column && $this->{$column} === null) {
            $this->forceFill([$column => now()])->save();
        }
    }
}
