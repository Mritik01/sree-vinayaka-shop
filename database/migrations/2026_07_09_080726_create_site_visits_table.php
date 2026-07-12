<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('entry_path')->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamp('first_seen')->useCurrent();
            $table->timestamp('last_seen')->useCurrent()->useCurrentOnUpdate();

            $table->index('first_seen');
            $table->index('last_seen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
