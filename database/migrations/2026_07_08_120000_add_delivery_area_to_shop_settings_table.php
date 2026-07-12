<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->boolean('restrict_delivery_area')->default(false)->after('accepting_orders');
            // Makhanbhog Sweets' Google Maps pin (Thuthibari, Maharajganj, UP)
            $table->decimal('delivery_center_lat', 10, 7)->default(27.4242366)->after('restrict_delivery_area');
            $table->decimal('delivery_center_lng', 10, 7)->default(83.6925724)->after('delivery_center_lat');
            $table->decimal('delivery_radius_km', 5, 2)->default(6)->after('delivery_center_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn(['restrict_delivery_area', 'delivery_center_lat', 'delivery_center_lng', 'delivery_radius_km']);
        });
    }
};
