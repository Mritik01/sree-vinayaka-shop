<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('eta_minutes')->nullable()->after('status');
            $table->text('customer_note')->nullable()->after('payment_method');
            // 'customer' or 'admin' — customer-cancelled COD orders don't trigger the COD restriction
            $table->string('cancelled_by', 20)->nullable()->after('customer_note');
            $table->timestamp('confirmed_at')->nullable()->after('cancelled_by');
            $table->timestamp('out_for_delivery_at')->nullable()->after('confirmed_at');
            $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'eta_minutes',
                'customer_note',
                'cancelled_by',
                'confirmed_at',
                'out_for_delivery_at',
                'delivered_at',
                'cancelled_at',
            ]);
        });
    }
};
