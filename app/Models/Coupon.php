<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'usage_type',
        'auto_assign_limit',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function redeemers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_redemptions')
            ->withPivot('discount_amount')
            ->withTimestamps();
    }

    // customers this coupon is specifically assigned to (admin picks these from a customer's
    // record). A coupon with no assigned users at all stays open to everyone, unchanged from
    // today's behaviour — only having at least one assignment turns on the restriction.
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')->withTimestamps();
    }

    public function isRestricted(): bool
    {
        return $this->assignedUsers()->exists();
    }

    // "master coupon" — auto-attached to the first N new signups instead of being picked
    // per-customer by an admin. See MasterCouponAssigner, run right after account creation.
    public function isMasterCoupon(): bool
    {
        return !is_null($this->auto_assign_limit);
    }

    public function autoAssignSlotsRemaining(): int
    {
        if (!$this->isMasterCoupon()) {
            return 0;
        }

        return max(0, $this->auto_assign_limit - $this->assignedUsers()->count());
    }

    public function isAvailableFor(User $user): bool
    {
        return !$this->isRestricted() || $this->assignedUsers()->whereKey($user->id)->exists();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function discountFor(int $subtotal): int
    {
        $discount = $this->discount_type === 'percent'
            ? (int) round($subtotal * $this->discount_value / 100)
            : $this->discount_value;

        return min($discount, $subtotal);
    }
}
