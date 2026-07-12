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
            // CSS object-position value, e.g. "50% 30%" — lets the admin pick which part
            // of the photo stays visible when it's cropped into the card's fixed aspect ratio
            $table->string('image_position', 20)->default('50% 50%')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_position');
        });
    }
};
