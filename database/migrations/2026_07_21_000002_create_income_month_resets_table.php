<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit-only log of "Super Admin X clicked Reset Current Month on date Y" — it never
     * touches platform_income_records (a real earned-income ledger must never be mutated or
     * deleted by a UI button). The dashboard's "This Month" figures are always a live
     * GROUP BY on delivered_at, so the calendar rolling over to a new month already gives a
     * clean slate with zero data loss; this table exists purely so the dashboard can show
     * "this month was manually reset by X on Z" for transparency.
     */
    public function up(): void
    {
        Schema::create('income_month_resets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('admin_id')->constrained();
            $table->timestamp('reset_at');
            $table->timestamps();

            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_month_resets');
    }
};
