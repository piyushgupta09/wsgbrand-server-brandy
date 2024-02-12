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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->text('content')->nullable();
            $table->string('type'); // audio, video, image, text, file
            $table->foreignId('ledger_id')->constrained();
            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('id')->on('users'); // staff, fabri, manager
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('recevied_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('chatables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->morphs('chatable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('chatables');
    }
};