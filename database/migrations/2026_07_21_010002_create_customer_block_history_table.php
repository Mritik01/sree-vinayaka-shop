<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permanent, append-only audit trail — one row per block or unblock action, forever. Never
     * updated or deleted (matches this app's other audit ledgers, e.g. platform_income_records /
     * income_month_resets). The users table's own block_* columns are just "current state" for
     * fast reads (e.g. the auth middleware checking is_blocked on every request); this table is
     * the real history.
     */
    public function up(): void
    {
        Schema::create('customer_block_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('action'); // 'blocked' | 'unblocked'
            $table->string('reason')->nullable();
            $table->text('message')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('admin_id')->constrained();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_block_history');
    }
};
