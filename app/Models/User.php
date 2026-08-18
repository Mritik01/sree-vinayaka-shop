<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // predefined reasons an admin can pick from when blocking a customer — see
    // Admin\CustomerController::block() and admin/customers/_block-modal.blade.php. Mirrors the
    // OrderItem::REMOVAL_REASONS convention (a fixed key => label map plus a 'custom' escape
    // hatch) already used elsewhere in this app.
    public const BLOCK_REASONS = [
        'spam_orders' => 'Spam Orders',
        'fake_orders' => 'Fake Orders',
        'coupon_misuse' => 'Misuse of Offers/Coupons',
        'abusive_behavior' => 'Abusive Behavior',
        'fraudulent_activity' => 'Fraudulent Activity',
        'multiple_fake_accounts' => 'Multiple Fake Accounts',
        'payment_issues' => 'Payment Issues',
        'delivery_misconduct' => 'Delivery Misconduct',
        'other' => 'Other (Custom Reason)',
    ];

    // a clear, plain-language, non-scary default customer-facing message per reason — shown as a
    // starting point in the block modal (the admin can edit it before confirming) so the message
    // a customer actually sees is never a raw internal reason code or an empty box. 'other' has
    // no sensible generic default, so the admin must write their own for that one.
    public const DEFAULT_BLOCK_MESSAGES = [
        'spam_orders' => "We've noticed an unusually high number of orders placed from your account in a short time, which looks like spam activity. Please contact our support team if you'd like to sort this out.",
        'fake_orders' => 'One or more orders placed from your account could not be verified as genuine. Please contact our support team for help resolving this.',
        'coupon_misuse' => "We've noticed repeated misuse of discount coupons or offers on your account. Please contact our support team if you think this is a mistake.",
        'abusive_behavior' => 'Your account has been restricted due to abusive or inappropriate behavior towards our staff or delivery partners. Please contact our support team to discuss this.',
        'fraudulent_activity' => 'Your account has been restricted due to suspected fraudulent activity. Please contact our support team for more information.',
        'multiple_fake_accounts' => 'Your account appears to be linked to multiple duplicate accounts. Please contact our support team if you believe this is incorrect.',
        'payment_issues' => 'Your account has an unresolved payment issue from a previous order. Please contact our support team so we can sort this out together.',
        'delivery_misconduct' => "We've noticed repeated issues at the time of delivery (such as orders being refused or nobody being available). Please contact our support team for details.",
        'other' => '',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'last_abandoned_cart_notified_at',
        'reward_progress',
        'free_gifts_available',
        'locale',
        'is_blocked',
        'block_reason',
        'block_message',
        'block_notes',
        'blocked_by_admin_id',
        'blocked_at',
        'unblocked_by_admin_id',
        'unblocked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_abandoned_cart_notified_at' => 'datetime',
        'password' => 'hashed',
        'reward_progress' => 'integer',
        'free_gifts_available' => 'integer',
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }

    public function blockReasonLabel(): ?string
    {
        return $this->block_reason ? (self::BLOCK_REASONS[$this->block_reason] ?? $this->block_reason) : null;
    }

    public static function defaultBlockMessageFor(string $reasonKey): string
    {
        return self::DEFAULT_BLOCK_MESSAGES[$reasonKey] ?? '';
    }

    public function blockedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'blocked_by_admin_id');
    }

    public function unblockedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'unblocked_by_admin_id');
    }

    // the permanent, never-deleted audit trail — see CustomerBlockHistory and
    // database/migrations/2026_07_21_010002_create_customer_block_history_table.php
    public function blockHistory(): HasMany
    {
        return $this->hasMany(CustomerBlockHistory::class)->latest('occurred_at');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function cart(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'cart_items')->withPivot('quantity', 'portion')->withTimestamps();
    }

    public function redeemedCoupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_redemptions')
            ->withPivot(['discount_amount', 'redeemed_at'])
            ->withTimestamps();
    }

    public function appliedCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'applied_coupon_id');
    }

    // coupons an admin has specifically assigned to this customer — see Coupon::isRestricted()
    public function assignedCoupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_user')->withTimestamps();
    }

    public function cartSubtotal(): int
    {
        return (int) $this->cart()->get()->sum(
            fn ($product) => $product->priceForPortion($product->pivot->portion ?: null) * $product->pivot->quantity
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // orders.user_id cascades on delete (unlike most of this table's other relations), so
    // hard-deleting a customer with any order history would silently wipe it — and
    // customer_block_history.user_id has no cascade at all (permanent audit ledger, see that
    // migration's docblock), so a previously blocked/unblocked customer can't be deleted anyway.
    // Used to gate Admin\CustomerController::destroy() before it ever reaches the database.
    public function isDeletable(): bool
    {
        return !$this->orders()->exists() && !$this->blockHistory()->exists();
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest();
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class)->latest('accepted_at');
    }
}
