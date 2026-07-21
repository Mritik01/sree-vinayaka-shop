<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Raw SQL rather than Schema::table(...)->change() — no doctrine/dbal installed (same approach
// as the two other nullable-column fixes this week).
// Same class of bug as make_description_nullable_on_products_table: validation already treated
// both columns as optional — 'weight' is `required_if:type,piece|nullable` (a loose-type product
// uses `portions` instead and legitimately has no weight), 'tag' is plain `nullable` — but neither
// column had NULL or a default at the DB level, so the "legal per validation" empty case still
// threw a raw SQL integrity-constraint error instead of the friendly validation message.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY weight VARCHAR(255) NULL');
        DB::statement('ALTER TABLE products MODIFY tag VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE products SET weight = '' WHERE weight IS NULL");
        DB::statement("UPDATE products SET tag = '' WHERE tag IS NULL");
        DB::statement('ALTER TABLE products MODIFY weight VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY tag VARCHAR(255) NOT NULL');
    }
};
