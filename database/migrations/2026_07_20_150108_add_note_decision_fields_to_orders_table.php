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
            // null | 'pending' | 'accepted' | 'denied' — mirrors the removed_at/removed_by_admin_id
            // audit-trail shape already used for order-item removal
            $table->string('note_status', 20)->nullable()->after('customer_note');
            $table->text('note_decision_message')->nullable()->after('note_status');
            $table->foreignId('note_decided_by_admin_id')->nullable()->after('note_decision_message')->constrained('admins')->nullOnDelete();
            $table->timestamp('note_decided_at')->nullable()->after('note_decided_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('note_decided_by_admin_id');
            $table->dropColumn(['note_status', 'note_decision_message', 'note_decided_at']);
        });
    }
};
