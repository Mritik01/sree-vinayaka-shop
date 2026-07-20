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
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->after('confirmed_at');
            $table->foreignId('removed_by_admin_id')->nullable()->after('removed_at')->constrained('admins')->nullOnDelete();
            // 'out_of_stock' | 'damaged' | 'quality_issue' | 'custom' — see OrderItem::REMOVAL_REASONS
            $table->string('removal_reason', 20)->nullable()->after('removed_by_admin_id');
            $table->string('removal_reason_note', 255)->nullable()->after('removal_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['removed_by_admin_id']);
            $table->dropColumn(['removed_at', 'removed_by_admin_id', 'removal_reason', 'removal_reason_note']);
        });
    }
};
