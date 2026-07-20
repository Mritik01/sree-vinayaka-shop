<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('featured_category_product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('featured_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['featured_category_id', 'product_tag_id'], 'fc_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_category_product_tag');
        Schema::dropIfExists('featured_categories');
    }
};
