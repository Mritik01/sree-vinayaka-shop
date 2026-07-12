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
        Schema::table('coupons', function (Blueprint $table) {
            // set = this is a "master coupon": auto-attached to that many of the newest
            // signups (first-come, first-served) until the slots run out. Null = not a master coupon.
            $table->unsignedInteger('auto_assign_limit')->nullable()->after('usage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('auto_assign_limit');
        });
    }
};
