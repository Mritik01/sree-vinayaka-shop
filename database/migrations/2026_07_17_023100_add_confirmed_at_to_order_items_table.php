<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // internal packing-verification checkmark only — an admin ticking this off while
            // physically packing an order. Never shown to the customer, never affects the
            // order's actual status/timeline.
            $table->timestamp('confirmed_at')->nullable()->after('is_gift');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
