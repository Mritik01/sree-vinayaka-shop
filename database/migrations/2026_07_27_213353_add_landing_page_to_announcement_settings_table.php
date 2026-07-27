<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Both are new (adding a column, adding a new table) — no doctrine/dbal needed, that's only
// required for ->change() on an existing column.
//
// landing_page_mode: 'none' (today's behavior — Shop Now uses the manual button_url as-is),
// 'custom' (a hand-picked, ordered list of products, see announcement_products below), or
// 'discounted' (every currently-discounted product, queried live — never a stored snapshot, so
// it can never go stale when a discount is added/removed elsewhere).
//
// announcement_products: the ordered product list for 'custom' mode. A real pivot table (not
// a JSON column on announcement_settings) so admin's product picker can reuse ordinary
// belongsToMany/sync() rather than hand-rolling array diffing, and so a deleted product cleanly
// disappears from the list via cascadeOnDelete instead of leaving a dangling id behind.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcement_settings', function (Blueprint $table) {
            $table->string('landing_page_mode')->default('none')->after('button_url');
        });

        Schema::create('announcement_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['announcement_setting_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_products');

        Schema::table('announcement_settings', function (Blueprint $table) {
            $table->dropColumn('landing_page_mode');
        });
    }
};
