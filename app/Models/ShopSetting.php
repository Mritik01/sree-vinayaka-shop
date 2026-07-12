<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSetting extends Model
{
    protected $fillable = [
        'accepting_orders',
        'restrict_delivery_area',
        'delivery_center_lat',
        'delivery_center_lng',
        'delivery_radius_km',
        'reward_enabled',
        'reward_orders_required',
        'reward_gift_product_id',
        'reward_gift_label',
        'min_order_amount',
        'max_order_amount',
    ];

    protected $casts = [
        'accepting_orders' => 'boolean',
        'restrict_delivery_area' => 'boolean',
        'delivery_center_lat' => 'float',
        'delivery_center_lng' => 'float',
        'delivery_radius_km' => 'float',
        'reward_enabled' => 'boolean',
        'reward_orders_required' => 'integer',
        'min_order_amount' => 'integer',
        'max_order_amount' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public static function acceptingOrders(): bool
    {
        return static::current()->accepting_orders;
    }

    public static function toggleAcceptingOrders(): bool
    {
        $settings = static::current();
        $settings->update(['accepting_orders' => !$settings->accepting_orders]);

        return $settings->accepting_orders;
    }

    public static function toggleRestrictDeliveryArea(): bool
    {
        $settings = static::current();
        $settings->update(['restrict_delivery_area' => !$settings->restrict_delivery_area]);

        return $settings->restrict_delivery_area;
    }

    public function rewardGiftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'reward_gift_product_id');
    }

    // the loyalty program only actually runs once the admin has both turned it on
    // AND picked a real product to give away — a label with no product has no fixed value to hand over
    public function rewardConfigured(): bool
    {
        return $this->reward_enabled && $this->reward_gift_product_id !== null;
    }

    // great-circle (haversine) distance in km from the shop to the given point
    public function distanceFromShopKm(float $latitude, float $longitude): float
    {
        $earthRadiusKm = 6371;

        $latFrom = deg2rad($this->delivery_center_lat);
        $lngFrom = deg2rad($this->delivery_center_lng);
        $latTo = deg2rad($latitude);
        $lngTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadiusKm * asin(sqrt($a));
    }
}
