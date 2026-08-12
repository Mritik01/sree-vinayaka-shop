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
            // master kill switch — everything below is inert while this is false, regardless of
            // its own toggle (see AiSensyService::send())
            $table->boolean('aisensy_enabled')->default(false)->after('razorpay_enabled');

            // per-message-type toggles so the admin can turn off e.g. abandoned-cart reminders
            // without disabling order-status WhatsApp messages, or vice versa. No standalone
            // "order placed" toggle — the shop only wants one message, sent when the order is
            // confirmed, not a separate one at checkout too.
            $table->boolean('aisensy_notify_order_confirmed')->default(false)->after('aisensy_enabled');
            $table->boolean('aisensy_notify_out_for_delivery')->default(false)->after('aisensy_notify_order_confirmed');
            $table->boolean('aisensy_notify_delivered')->default(false)->after('aisensy_notify_out_for_delivery');
            $table->boolean('aisensy_notify_cancelled')->default(false)->after('aisensy_notify_delivered');
            $table->boolean('aisensy_notify_abandoned_cart')->default(false)->after('aisensy_notify_cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'aisensy_enabled',
                'aisensy_notify_order_confirmed',
                'aisensy_notify_out_for_delivery',
                'aisensy_notify_delivered',
                'aisensy_notify_cancelled',
                'aisensy_notify_abandoned_cart',
            ]);
        });
    }
};
