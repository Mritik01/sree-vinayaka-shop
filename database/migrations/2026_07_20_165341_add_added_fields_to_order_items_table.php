<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit trail for items an admin adds to an already-placed order (see
     * Admin\OrderController::addItems()). Mirrors the removed_by_admin_id/removed_at pair.
     * Original checkout items keep both NULL; a non-null added_at is what marks a line as
     * admin-added (drives the customer "added" flag + the one-time "order updated" popup).
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('added_by_admin_id')->nullable()->after('removal_reason_note')->constrained('admins')->nullOnDelete();
            $table->timestamp('added_at')->nullable()->after('added_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['added_by_admin_id']);
            $table->dropColumn(['added_by_admin_id', 'added_at']);
        });
    }
};
