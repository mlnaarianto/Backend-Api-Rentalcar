<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel utama room chat (bisa antar 2 user)
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->text('last_message')->nullable();
            $table->foreignId('last_sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabel isi pesan di dalam room chat
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('text');
            $table->timestamps(); // Ini otomatis jadi created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
    }
};