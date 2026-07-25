<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Both are new columns (not modifying an existing one), so the normal Schema Builder works fine
// here — doctrine/dbal is only needed for ->change() on an existing column (see the nearby
// "make X nullable" migrations), not for adding new ones.
//
// unit: a loose product's portions were always grams (200g/500g/1kg...) — a bottled liquid like
// handwash needs the same "multiple sizes, price scales from a base" idea but in ml/L instead
// (see Product::portionLabel()). Defaults to 'weight' so every existing loose product keeps
// showing g/kg exactly as before.
//
// portion_images: a 25ml handwash bottle and a 1L one don't look alike, so a single product-wide
// image isn't enough once portion_prices already lets each size have its own price — this is the
// same per-portion-override pattern (Product::portionOverrideImage()), just for the photo.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit')->default('weight')->after('type');
            $table->json('portion_images')->nullable()->after('portion_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit', 'portion_images']);
        });
    }
};
