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
            // whichever rider first acts on this order (marks out-for-delivery, delivered,
            // or uploads the proof photo) — set once, kept for accountability, never reassigned
            $table->foreignId('rider_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('delivery_photo_path')->nullable()->after('cancelled_at');
            $table->timestamp('delivery_photo_uploaded_at')->nullable()->after('delivery_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['rider_id']);
            $table->dropColumn(['rider_id', 'delivery_photo_path', 'delivery_photo_uploaded_at']);
        });
    }
};
