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
            // Shree Vinayak Family Shop' Google Maps pin (Siswa Bazar, Maharajganj, UP)
            $table->decimal('delivery_center_lat', 10, 7)->default(27.1555580)->after('restrict_delivery_area');
            $table->decimal('delivery_center_lng', 10, 7)->default(83.7584378)->after('delivery_center_lat');
            $table->decimal('delivery_radius_km', 5, 2)->default(7)->after('delivery_center_lng');
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
