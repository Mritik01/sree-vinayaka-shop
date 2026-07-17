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
        Schema::create('order_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // 'delivery' | 'rain' | 'high_demand' | future fee types
            // snapshotted at order time — never changes even if the admin edits the message/amount
            // later, so a past order's invoice always shows exactly what the customer was charged
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->index(['order_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_fees');
    }
};
