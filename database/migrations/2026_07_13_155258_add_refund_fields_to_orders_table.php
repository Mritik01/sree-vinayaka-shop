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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_status')->default('none')->after('paid_at'); // none|partial|full
            $table->unsignedInteger('refunded_amount')->default(0)->after('refund_status');
            $table->string('razorpay_refund_id')->nullable()->after('refunded_amount');
            $table->timestamp('refunded_at')->nullable()->after('razorpay_refund_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refunded_amount', 'razorpay_refund_id', 'refunded_at']);
        });
    }
};
