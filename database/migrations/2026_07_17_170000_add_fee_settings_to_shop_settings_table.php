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
            // Delivery Fee — one of two strategies, see ShopSetting::deliveryFeeFor()
            $table->string('delivery_fee_strategy')->default('fixed'); // 'free_above_minimum' | 'fixed'
            $table->unsignedInteger('delivery_free_min_order')->nullable();
            $table->unsignedInteger('delivery_fee_below_minimum')->nullable();
            $table->unsignedInteger('delivery_fee_fixed')->default(0);
            $table->string('delivery_success_message')->default('Free Delivery Unlocked! 🚚');
            $table->string('delivery_success_animation')->default('confetti_truck'); // 'confetti_truck' | 'minimal'

            // Rain Fee
            $table->boolean('rain_fee_enabled')->default(false);
            $table->unsignedInteger('rain_fee_amount')->nullable();
            $table->string('rain_fee_reason')->nullable()->default('Heavy Rain');
            $table->text('rain_fee_message')->nullable();

            // High Demand Mode
            $table->string('high_demand_mode')->default('normal'); // 'normal' | 'stop' | 'fee'
            $table->unsignedInteger('high_demand_fee_amount')->nullable();
            $table->text('high_demand_message')->nullable();
            $table->text('high_demand_stop_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_fee_strategy', 'delivery_free_min_order', 'delivery_fee_below_minimum',
                'delivery_fee_fixed', 'delivery_success_message', 'delivery_success_animation',
                'rain_fee_enabled', 'rain_fee_amount', 'rain_fee_reason', 'rain_fee_message',
                'high_demand_mode', 'high_demand_fee_amount', 'high_demand_message', 'high_demand_stop_message',
            ]);
        });
    }
};
