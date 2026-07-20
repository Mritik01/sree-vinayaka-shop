<?php

namespace App\Providers;

use App\Models\AnnouncementSetting;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // guarded so a missing table (first `migrate`) or an unreachable DB doesn't crash every request at boot
        $shopStatus = [
            'accepting' => true, 'restricted' => false, 'radiusKm' => 6.0,
            'deliveryFeeStrategy' => 'fixed', 'deliveryFreeMinOrder' => null, 'deliveryFeeBelowMinimum' => null,
            'deliveryFeeFixed' => 0, 'deliverySuccessMessage' => 'Free Delivery Unlocked! 🚚', 'deliverySuccessAnimation' => 'confetti_truck',
            'rainFeeEnabled' => false, 'rainFeeAmount' => 0, 'rainFeeMessage' => null,
            'highDemandMode' => 'normal', 'highDemandFeeAmount' => 0, 'highDemandFeeMessage' => null, 'highDemandStopMessage' => null,
            'deliveryTimeMinutes' => null,
            'codEnabled' => true, 'razorpayEnabled' => true,
        ];
        $promoPopupEnabled = true;
        // the business contact number, shared site-wide — see ShopSetting::businessPhone*() for
        // the tel:/wa.me/display formatting. Not part of $shopStatus/pollShopStatus() on purpose:
        // this only needs to change on the next page load, not live within an open tab, so a
        // plain View::share (recomputed once per request) is simpler than wiring it into the 5s poll
        $businessPhone = null;
        try {
            if (Schema::hasTable('shop_settings')) {
                $settings = ShopSetting::current();
                $businessPhone = $settings->business_mobile_number ? [
                    'digits' => $settings->business_mobile_number,
                    'display' => $settings->businessPhoneDisplay(),
                    'tel' => $settings->businessPhoneTelHref(),
                    'whatsapp' => $settings->businessWhatsappHref(),
                ] : null;
                $shopStatus = [
                    'accepting' => $settings->accepting_orders,
                    'restricted' => $settings->restrict_delivery_area,
                    'radiusKm' => $settings->delivery_radius_km,
                    // fee config — the single source of truth is ShopSetting::activeFees()/deliveryFeeFor();
                    // this just exposes the same inputs to the client for an instant preview, refreshed
                    // live by pollShopStatus() in app.js so an admin change needs no page reload anywhere
                    'deliveryFeeStrategy' => $settings->delivery_fee_strategy,
                    'deliveryFreeMinOrder' => $settings->delivery_free_min_order,
                    'deliveryFeeBelowMinimum' => $settings->delivery_fee_below_minimum,
                    'deliveryFeeFixed' => $settings->delivery_fee_fixed,
                    'deliverySuccessMessage' => $settings->delivery_success_message,
                    'deliverySuccessAnimation' => $settings->delivery_success_animation,
                    'rainFeeEnabled' => $settings->rain_fee_enabled,
                    'rainFeeAmount' => $settings->rain_fee_amount ?? 0,
                    'rainFeeMessage' => $settings->rain_fee_enabled ? $settings->rainFeeMessage() : null,
                    'highDemandMode' => $settings->high_demand_mode,
                    'highDemandFeeAmount' => $settings->high_demand_fee_amount ?? 0,
                    'highDemandFeeMessage' => $settings->high_demand_mode === 'fee' ? $settings->highDemandFeeMessage() : null,
                    'highDemandStopMessage' => $settings->high_demand_mode === 'stop' ? $settings->highDemandStopMessage() : null,
                    'deliveryTimeMinutes' => $settings->delivery_time_estimate_minutes,
                    'codEnabled' => $settings->cod_enabled,
                    'razorpayEnabled' => $settings->razorpay_enabled,
                ];
                $promoPopupEnabled = $settings->promo_popup_enabled;
            }
        } catch (\Throwable $e) {
            // fall back to permissive defaults; checkout still re-checks against the real DB
        }

        View::share('acceptingOrders', $shopStatus['accepting']);
        View::share('shopStatus', $shopStatus);
        View::share('promoPopupEnabled', $promoPopupEnabled);
        View::share('businessPhone', $businessPhone);

        // guarded the same way as $shopStatus above — a missing table or unreachable DB
        // must never crash every page, it just means no banner shows this request
        $announcement = null;
        try {
            if (Schema::hasTable('announcement_settings')) {
                $setting = AnnouncementSetting::current();
                if ($setting->isLive()) {
                    $announcement = [
                        'headline' => $setting->headline,
                        'description' => $setting->description,
                        'buttonText' => $setting->button_text,
                        'buttonUrl' => $setting->button_url,
                        'image' => $setting->image_path ? asset($setting->image_path) : null,
                        'theme' => $setting->theme,
                        'backgroundColor' => $setting->background_color,
                        'textColor' => $setting->text_color,
                        'frequency' => $setting->display_frequency,
                        'showClose' => $setting->show_close_button,
                        'autoCloseSeconds' => $setting->auto_close_seconds,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // fall back to no banner
        }

        View::share('announcement', $announcement);
    }
}
