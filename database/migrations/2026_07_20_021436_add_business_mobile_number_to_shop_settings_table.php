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
            // seeded to the number already hardcoded everywhere on the site today (not null) so
            // this migration is a no-op for customers — every existing contact link keeps working
            // identically the instant it runs, and only changes once an admin edits it
            $table->string('business_mobile_number', 10)->nullable()->default('8920937331');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn('business_mobile_number');
        });
    }
};
