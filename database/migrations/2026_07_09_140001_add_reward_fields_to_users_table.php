<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // delivered orders completed since the last free-gift claim (resets to 0 on claim)
            $table->unsignedInteger('reward_progress')->default(0);
            // banked, unclaimed free-gift credits — incremented when reward_progress hits the threshold
            $table->unsignedInteger('free_gifts_available')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reward_progress', 'free_gifts_available']);
        });
    }
};
