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
        'reward_progress',
        'free_gifts_available',
        'locale',
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
        'password' => 'hashed',
        'reward_progress' => 'integer',
        'free_gifts_available' => 'integer',
    ];

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
}
