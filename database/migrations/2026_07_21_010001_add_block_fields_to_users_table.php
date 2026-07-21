<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Current block state only — the permanent, never-deleted record of every block/unblock
     * ever performed lives in customer_block_history (see that migration). These columns are
     * intentionally NOT cleared on unblock (only is_blocked flips + unblocked_by/unblocked_at
     * get stamped) so the "last known reason" stays visible on the Account Status card even
     * after a customer is unblocked — re-blocking later simply overwrites them again.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('locale');
            $table->string('block_reason')->nullable()->after('is_blocked');
            $table->text('block_message')->nullable()->after('block_reason');
            $table->text('block_notes')->nullable()->after('block_message');
            $table->foreignId('blocked_by_admin_id')->nullable()->after('block_notes')->constrained('admins')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable()->after('blocked_by_admin_id');
            $table->foreignId('unblocked_by_admin_id')->nullable()->after('blocked_at')->constrained('admins')->nullOnDelete();
            $table->timestamp('unblocked_at')->nullable()->after('unblocked_by_admin_id');

            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocked_by_admin_id');
            $table->dropConstrainedForeignId('unblocked_by_admin_id');
            $table->dropColumn(['is_blocked', 'block_reason', 'block_message', 'block_notes', 'blocked_at', 'unblocked_at']);
        });
    }
};
