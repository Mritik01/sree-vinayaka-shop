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
        $shopStatus = ['accepting' => true, 'restricted' => false, 'radiusKm' => 6.0];
        $promoPopupEnabled = true;
        try {
            if (Schema::hasTable('shop_settings')) {
                $settings = ShopSetting::current();
                $shopStatus = [
                    'accepting' => $settings->accepting_orders,
                    'restricted' => $settings->restrict_delivery_area,
                    'radiusKm' => $settings->delivery_radius_km,
                ];
                $promoPopupEnabled = $settings->promo_popup_enabled;
            }
        } catch (\Throwable $e) {
            // fall back to permissive defaults; checkout still re-checks against the real DB
        }

        View::share('acceptingOrders', $shopStatus['accepting']);
        View::share('shopStatus', $shopStatus);
        View::share('promoPopupEnabled', $promoPopupEnabled);

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
