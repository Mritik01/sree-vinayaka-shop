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
        Schema::table('orders', function (Blueprint $table) {
            // frozen the instant Razorpay actually confirms payment (see RazorpayController) and
            // never touched again — unlike `total`, which Order::recalculateTotals() can now
            // shrink after an item removal. refundableAmount() must refund against what was
            // genuinely captured, not against a total that's since moved.
            $table->unsignedInteger('amount_paid')->nullable()->after('total');
        });

        // backfill: for every already-paid Razorpay order, `total` right now still equals what
        // was captured (nothing has recalculated it yet) — this only stops being true going
        // forward, once item removal exists
        DB::table('orders')
            ->where('payment_method', 'razorpay')
            ->where('payment_status', 'paid')
            ->update(['amount_paid' => DB::raw('total')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
