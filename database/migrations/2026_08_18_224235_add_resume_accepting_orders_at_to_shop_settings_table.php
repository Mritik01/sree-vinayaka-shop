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
        Schema::table('shop_settings', function (Blueprint $table) {
            // set when an admin turns off "Accepting Orders" and picks a time to reopen —
            // null means either still accepting, or paused with no scheduled reopen time.
            // Cleared automatically the moment orders resume (manually or on schedule), so its
            // mere presence always means "currently paused, with a reopen time in the future"
            $table->timestamp('resume_accepting_orders_at')->nullable()->after('accepting_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn('resume_accepting_orders_at');
        });
    }
};
