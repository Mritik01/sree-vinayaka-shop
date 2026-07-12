<?php

namespace App\Providers;

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
        try {
            if (Schema::hasTable('shop_settings')) {
                $settings = ShopSetting::current();
                $shopStatus = [
                    'accepting' => $settings->accepting_orders,
                    'restricted' => $settings->restrict_delivery_area,
                    'radiusKm' => $settings->delivery_radius_km,
                ];
            }
        } catch (\Throwable $e) {
            // fall back to permissive defaults; checkout still re-checks against the real DB
        }

        View::share('acceptingOrders', $shopStatus['accepting']);
        View::share('shopStatus', $shopStatus);
    }
}
