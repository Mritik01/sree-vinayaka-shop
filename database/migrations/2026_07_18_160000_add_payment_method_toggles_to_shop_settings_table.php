<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->boolean('cod_enabled')->default(true)->after('show_category_row');
            $table->boolean('razorpay_enabled')->default(true)->after('cod_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn(['cod_enabled', 'razorpay_enabled']);
        });
    }
};
