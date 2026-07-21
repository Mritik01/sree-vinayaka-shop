<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Raw SQL rather than Schema::table(...)->change() — this app doesn't have doctrine/dbal
// installed, which Laravel 10's Schema Builder requires for column modification (same
// approach as 2026_07_21_160055_make_title_nullable_on_hero_banners_table).
// Validation already allowed a blank description ('description' => 'nullable|string' in
// ProductController::validateData()), but the column itself was NOT NULL with no default,
// so submitting the admin form with the field left empty threw a raw SQL integrity-constraint
// error instead of the friendly validation message the admin should have seen.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY description VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE products SET description = '' WHERE description IS NULL");
        DB::statement('ALTER TABLE products MODIFY description VARCHAR(255) NOT NULL');
    }
};
