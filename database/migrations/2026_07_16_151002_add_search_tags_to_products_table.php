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
            // the clean, ordered list of keywords/synonyms — what the admin edits (chip UI)
            // and what gets round-tripped back into the edit form
            $table->json('search_tags')->nullable()->after('tag');

            // auto-derived from search_tags on every save (see Product::booted()) — a single
            // lowercase, space-joined string so search can stay a plain indexed LIKE query
            // instead of needing JSON-aware SQL functions; keeps it fast even at scale
            $table->string('search_tags_flat', 500)->nullable()->after('search_tags');
            $table->index('search_tags_flat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['search_tags_flat']);
            $table->dropColumn(['search_tags', 'search_tags_flat']);
        });
    }
};
