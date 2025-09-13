<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->text('content');
            $table->boolean('is_sent')->default(false);
            $table->string('message_id', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('is_sent');
            $table->index('created_at');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
