<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // when a COD order they placed gets cancelled (they didn't accept it), this counts
            // down how many of their next orders must go through online payment instead of COD
            $table->unsignedTinyInteger('cod_blocked_orders')->default(0)->after('applied_coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cod_blocked_orders');
        });
    }
};
