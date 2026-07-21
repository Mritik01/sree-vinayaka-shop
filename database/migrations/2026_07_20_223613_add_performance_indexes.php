<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purely additive (no data change, no risk to existing rows/queries) — closes the gaps
     * flagged by the pre-deployment performance audit: orders.status/created_at/updated_at are
     * scanned by the admin panel's 5-second poll, the dashboard counts, and the auto-cancel
     * sweep; products.is_bestseller/is_festival_special are scanned on every homepage load;
     * coupons.is_active/expires_at are scanned on every admin dashboard load.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('is_bestseller');
            $table->index('is_festival_special');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_bestseller']);
            $table->dropIndex(['is_festival_special']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['expires_at']);
        });
    }
};
