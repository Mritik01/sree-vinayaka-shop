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
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'terms' | 'privacy'
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('content'); // sanitized HTML, see Admin\LegalDocumentController
            $table->timestamp('published_at');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // every save inserts a new row rather than updating in place — this is what
            // "version history" means here, so each (type, version) pair must be unique
            $table->unique(['type', 'version']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
    }
};
