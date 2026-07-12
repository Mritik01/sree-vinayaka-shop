<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];

    // statuses the customer is still allowed to cancel from — once the rider leaves, it's too late
    public const CUSTOMER_CANCELLABLE_STATUSES = ['pending', 'confirmed'];

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
        'status',
        'eta_minutes',
        'payment_method',
        'customer_note',
        'is_gift_order',
        'cancelled_by',
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
        'delivery_photo_uploaded_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isCustomerCancellable(): bool
    {
        return in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES);
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
