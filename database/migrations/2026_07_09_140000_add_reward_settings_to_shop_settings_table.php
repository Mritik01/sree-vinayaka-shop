<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->boolean('reward_enabled')->default(true);
            $table->unsignedInteger('reward_orders_required')->default(6);
            $table->foreignId('reward_gift_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('reward_gift_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reward_gift_product_id');
            $table->dropColumn(['reward_enabled', 'reward_orders_required', 'reward_gift_label']);
        });
    }
};
