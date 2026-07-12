<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 15)->unique()->after('name');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
