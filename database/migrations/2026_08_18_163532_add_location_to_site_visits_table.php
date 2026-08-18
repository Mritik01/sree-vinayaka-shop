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
        Schema::table('site_visits', function (Blueprint $table) {
            // resolved once per session (see IpGeolocationService + ActivityLogger::recordVisit()'s
            // new-session branch) — never re-looked-up per page view, and left null when the
            // lookup fails or the IP is private/local (both handled gracefully, not an error case)
            $table->string('city', 100)->nullable()->after('ip_address');
            $table->string('country', 100)->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropColumn(['city', 'country']);
        });
    }
};
