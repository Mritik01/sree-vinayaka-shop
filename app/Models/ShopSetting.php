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
        'max_orders_per_hour',
        'promo_popup_enabled',
        'delivery_time_estimate_minutes',
        'show_category_row',
        'cod_enabled',
        'razorpay_enabled',
        'delivery_fee_strategy',
        'delivery_free_min_order',
        'delivery_fee_below_minimum',
        'delivery_fee_fixed',
        'delivery_success_message',
        'delivery_success_animation',
        'rain_fee_enabled',
        'rain_fee_amount',
        'rain_fee_reason',
        'rain_fee_message',
        'high_demand_mode',
        'high_demand_fee_amount',
        'high_demand_message',
        'high_demand_stop_message',
        'business_mobile_number',
        'business_logo_path',
        'customer_theme',
        'aisensy_enabled',
        'aisensy_notify_order_confirmed',
        'aisensy_notify_out_for_delivery',
        'aisensy_notify_delivered',
        'aisensy_notify_cancelled',
        'aisensy_notify_abandoned_cart',
    ];

    protected $casts = [
        'accepting_orders' => 'boolean',
        'restrict_delivery_area' => 'boolean',
        'delivery_center_lat' => 'float',
        'delivery_center_lng' => 'float',
        'delivery_radius_km' => 'float',
        'reward_enabled' => 'boolean',
        'promo_popup_enabled' => 'boolean',
        'show_category_row' => 'boolean',
        'cod_enabled' => 'boolean',
        'razorpay_enabled' => 'boolean',
        'reward_orders_required' => 'integer',
        'min_order_amount' => 'integer',
        'max_order_amount' => 'integer',
        'max_orders_per_hour' => 'integer',
        'delivery_time_estimate_minutes' => 'integer',
        'delivery_free_min_order' => 'integer',
        'delivery_fee_below_minimum' => 'integer',
        'delivery_fee_fixed' => 'integer',
        'rain_fee_enabled' => 'boolean',
        'rain_fee_amount' => 'integer',
        'high_demand_fee_amount' => 'integer',
        'aisensy_enabled' => 'boolean',
        'aisensy_notify_order_confirmed' => 'boolean',
        'aisensy_notify_out_for_delivery' => 'boolean',
        'aisensy_notify_delivered' => 'boolean',
        'aisensy_notify_cancelled' => 'boolean',
        'aisensy_notify_abandoned_cart' => 'boolean',
    ];

    // request-level memoization only — this singleton row is read repeatedly per request (once
    // in AppServiceProvider's shared view data, then again from controllers/Blade partials that
    // each call current() independently), and was previously a fresh SELECT every single time.
    // Safe: within one request, ->update() mutates this SAME cached instance in place (PHP
    // objects are held by reference), so a later current() call in the same request still sees
    // the fresh value, not stale data — only the redundant duplicate queries are eliminated.
    // Static properties reset naturally between requests under the traditional PHP-FPM/CLI
    // lifecycle this app runs under; this would need revisiting if the app ever moves to a
    // persistent-worker runtime like Octane, where statics can leak across requests.
    private static ?self $cached = null;

    public static function current(): self
    {
        return static::$cached ??= static::firstOrCreate([]);
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

    // stored as bare 10 digits (no +91, no spaces) — these three format it consistently for every
    // call site instead of each view/controller reformatting it its own way
    public function businessPhoneDisplay(): ?string
    {
        return $this->business_mobile_number
            ? '+91 '.substr($this->business_mobile_number, 0, 5).' '.substr($this->business_mobile_number, 5)
            : null;
    }

    public function businessPhoneTelHref(): ?string
    {
        return $this->business_mobile_number ? 'tel:+91'.$this->business_mobile_number : null;
    }

    public function businessWhatsappHref(?string $text = null): ?string
    {
        if (!$this->business_mobile_number) {
            return null;
        }

        $url = 'https://wa.me/91'.$this->business_mobile_number;

        return $text ? $url.'?text='.rawurlencode($text) : $url;
    }

    // null business_logo_path (the default, and what every existing install has today) falls back
    // to the static asset every customer-facing view already used before this feature existed —
    // see Admin\CustomizationController for how an uploaded logo gets here
    public function businessLogoUrl(): string
    {
        return $this->business_logo_path ? asset($this->business_logo_path) : asset('images/logo-circle.png');
    }

    // the active theme's full CSS-variable definition — see config/customer_themes.php. Falls back
    // to the default if customer_theme somehow holds a slug that's no longer configured (e.g. a
    // theme was removed from the config after being selected)
    public function customerThemeConfig(): array
    {
        return config('customer_themes.'.$this->customer_theme) ?? config('customer_themes.maroon_gold');
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

    public function deliveryFeeFor(int $subtotal): int
    {
        if ($this->delivery_fee_strategy === 'free_above_minimum') {
            $threshold = $this->delivery_free_min_order ?? 0;

            return $subtotal >= $threshold ? 0 : (int) ($this->delivery_fee_below_minimum ?? 0);
        }

        return (int) $this->delivery_fee_fixed;
    }

    // how much more the customer needs to add to their cart to unlock free delivery —
    // 0 once they're already there, or always under the fixed-fee strategy (nothing to unlock)
    public function amountToFreeDelivery(int $subtotal): int
    {
        if ($this->delivery_fee_strategy !== 'free_above_minimum') {
            return 0;
        }

        return max(0, ($this->delivery_free_min_order ?? 0) - $subtotal);
    }

    public function rainFeeMessage(): string
    {
        if ($this->rain_fee_message) {
            return $this->rain_fee_message;
        }

        $reason = $this->rain_fee_reason ?: 'heavy rainfall';

        return "🌧️ Due to {$reason}, an additional ₹{$this->rain_fee_amount} Rain Fee has been added to support our delivery partners. Thank you for your understanding ❤️";
    }

    public function highDemandFeeMessage(): string
    {
        if ($this->high_demand_message) {
            return $this->high_demand_message;
        }

        return "⚡ We're experiencing a high volume of orders. A temporary High Demand Fee of ₹{$this->high_demand_fee_amount} has been added to help us maintain fast deliveries. Thank you for your support ❤️";
    }

    public function highDemandStopMessage(): string
    {
        return $this->high_demand_stop_message
            ?: "🚫 We're currently experiencing exceptionally high demand and are temporarily unable to accept new orders. Please try again in a little while. Thank you for your patience ❤️";
    }

    // single source of truth for every operational fee — called both by the live customer-facing
    // preview (/shop-status) and by CheckoutController::store() as the authoritative charge.
    // Adding a future fee (festival, night delivery, weather surcharge...) is just a new branch
    // here plus its own ShopSetting columns — orders/order_fees and every display template that
    // loops over $order->fees never need to change.
    public function activeFees(int $subtotal): array
    {
        $fees = [];

        $deliveryFee = $this->deliveryFeeFor($subtotal);
        if ($deliveryFee > 0) {
            $fees[] = ['key' => 'delivery', 'label' => 'Delivery Fee', 'amount' => $deliveryFee];
        }

        if ($this->rain_fee_enabled && $this->rain_fee_amount > 0) {
            $label = 'Rain Fee'.($this->rain_fee_reason ? " — {$this->rain_fee_reason}" : '');
            $fees[] = ['key' => 'rain', 'label' => $label, 'amount' => (int) $this->rain_fee_amount];
        }

        if ($this->high_demand_mode === 'fee' && $this->high_demand_fee_amount > 0) {
            $fees[] = ['key' => 'high_demand', 'label' => 'High Demand Fee', 'amount' => (int) $this->high_demand_fee_amount];
        }

        return $fees;
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
