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
            $table->unsignedInteger('min_order_amount')->nullable()->after('reward_gift_label');
            $table->unsignedInteger('max_order_amount')->nullable()->after('min_order_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn(['min_order_amount', 'max_order_amount']);
        });
    }
};
