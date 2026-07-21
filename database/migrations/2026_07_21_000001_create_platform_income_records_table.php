<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One permanent, append-only row per delivered order — the single source of truth for
     * every "Income" dashboard/history figure (all of which are just date-range aggregations
     * over this table, e.g. GROUP BY YEAR/MONTH(delivered_at)). Nothing ever updates or deletes
     * a row here once written; "monthly reset" is purely a dashboard view concept (see
     * IncomeMonthReset) rather than a data mutation, so historical income can never be lost.
     * unique(order_id) is the hard database-level guarantee behind PlatformIncomeService's
     * idempotency — even a race between two requests recording the same order's delivery can't
     * produce two rows.
     */
    public function up(): void
    {
        Schema::create('platform_income_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained();
            $table->foreignId('rider_id')->nullable()->constrained()->nullOnDelete();
            // snapshot, not a live join — matches Order::$customer_name's own convention, and
            // survives regardless of what happens to the order/rider row later
            $table->string('customer_name');
            $table->unsignedInteger('order_amount');
            $table->unsignedInteger('delivery_charge')->default(0);
            $table->unsignedInteger('fixed_commission');
            $table->unsignedInteger('delivery_charge_income')->default(0);
            $table->unsignedInteger('total_income');
            $table->timestamp('delivered_at');
            $table->timestamps();

            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_income_records');
    }
};
