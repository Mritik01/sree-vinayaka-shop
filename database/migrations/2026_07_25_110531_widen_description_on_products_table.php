<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Raw SQL rather than Schema::table(...)->change() — this app doesn't have doctrine/dbal
// installed, which Laravel 10's Schema Builder requires for column modification (same approach
// as 2026_07_21_172010_make_description_nullable_on_products_table).
// description was VARCHAR(255) with no validation max length ('description' => 'nullable|string'
// in ProductController::validateData()), so a longer description passed validation but blew up
// with a raw "Data too long for column" SQL error (uncaught -> 500) once it hit the database.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY description TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY description VARCHAR(255) NULL');
    }
};
