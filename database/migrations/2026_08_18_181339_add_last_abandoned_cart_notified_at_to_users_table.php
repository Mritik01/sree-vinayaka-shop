<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // set whenever AbandonedCartReminderService::sendReminderTo() actually sends (manual
            // button or the on-demand carts:remind-abandoned command) — powers the 24h cooldown on
            // the admin customer page's "Notify Abandoned Cart" button (see CustomerController::show())
            $table->timestamp('last_abandoned_cart_notified_at')->nullable()->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_abandoned_cart_notified_at');
        });
    }
};
