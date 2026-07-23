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
        Schema::table('products', function (Blueprint $table) {
            // optional {grams: price} overrides for a loose product's portions — e.g. a
            // pre-packaged item (Surf Excel 500g/750g/1kg) where each pack size has its own
            // real-world price, not a clean multiple of the 250g base (see
            // Product::priceForPortion()). Null/missing entries keep the original linear
            // "price * grams/250" behaviour, so existing loose products (priced genuinely by
            // weight, like loose mithai) are unaffected.
            $table->json('portion_prices')->nullable()->after('portions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('portion_prices');
        });
    }
};
