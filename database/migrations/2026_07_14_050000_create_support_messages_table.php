<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('sender', 10); // 'customer' | 'admin'
            $table->text('message');
            // read by the OTHER side — a customer message's read_at is stamped when the admin
            // opens the thread; an admin message's when the customer has the chat window open
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'id']);
            $table->index(['sender', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
