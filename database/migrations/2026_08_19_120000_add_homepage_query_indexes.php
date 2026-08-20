<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// speeds up the homepage's category/bestseller/festival-special queries (see routes/web.php's
// '/' closure) as the catalog grows — negligible today at "dozens-to-low-hundreds of products"
// per existing code comments, but cheap insurance. NOTE: this file is for local dev / codebase
// parity only — production deploys via FTP with no artisan access, so these same 3 indexes must
// be applied there by hand via phpMyAdmin's SQL tab:
//
//   ALTER TABLE `categories` ADD INDEX `categories_is_active_sort_order_index` (`is_active`, `sort_order`);
//   ALTER TABLE `products` ADD INDEX `products_is_bestseller_sort_order_index` (`is_bestseller`, `sort_order`);
//   ALTER TABLE `products` ADD INDEX `products_is_festival_special_sort_order_index` (`is_festival_special`, `sort_order`);
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_bestseller', 'sort_order']);
            $table->index(['is_festival_special', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_bestseller', 'sort_order']);
            $table->dropIndex(['is_festival_special', 'sort_order']);
        });
    }
};
