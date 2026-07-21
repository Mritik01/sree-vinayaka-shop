<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Raw SQL rather than Schema::table(...)->change() — this app doesn't have doctrine/dbal
// installed, which Laravel 10's Schema Builder requires for column modification.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE hero_banners MODIFY title VARCHAR(150) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE hero_banners SET title = '' WHERE title IS NULL");
        DB::statement('ALTER TABLE hero_banners MODIFY title VARCHAR(150) NOT NULL');
    }
};
