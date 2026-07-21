<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // defaults true so existing products keep showing in "Siswa Bazar's Favourites"
            // until the admin curates the list down via the new Bestsellers page
            $table->boolean('is_bestseller')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_bestseller');
        });
    }
};
