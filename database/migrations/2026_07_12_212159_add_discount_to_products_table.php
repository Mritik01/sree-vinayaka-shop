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
            // null discount_type = no discount active; keeps the "is it discounted" check
            // to a single nullable column, same pattern as portions being null for piece products
            $table->string('discount_type', 10)->nullable()->after('price'); // 'percentage' | 'flat'
            $table->unsignedInteger('discount_value')->nullable()->after('discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
    }
};
