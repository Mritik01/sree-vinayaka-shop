<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            // null = applied to a cart but not (yet) confirmed by a placed order — still freely
            // reusable/removable; set = genuinely spent, the coupon can never be reapplied
            $table->timestamp('redeemed_at')->nullable()->after('discount_amount');
        });

        // Backfill: a redemption row previously got created the moment a coupon was applied,
        // not when an order was actually placed with it — so an abandoned apply permanently
        // blocked reapplying the same code. Only rows backed by a real order are genuine
        // usage; everything else is a stale pending apply and should become reusable again.
        DB::table('coupon_redemptions')->orderBy('id')->get()->each(function ($redemption) {
            $order = DB::table('orders')
                ->where('user_id', $redemption->user_id)
                ->where('coupon_id', $redemption->coupon_id)
                ->orderBy('created_at')
                ->first();

            if ($order) {
                DB::table('coupon_redemptions')
                    ->where('id', $redemption->id)
                    ->update(['redeemed_at' => $order->created_at]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropColumn('redeemed_at');
        });
    }
};
