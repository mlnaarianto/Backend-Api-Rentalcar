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
        Schema::create('personal_data', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // Jika user dihapus, personal data ikut terhapus
            
            // Kolom data personal
            $table->string('phone')->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();

            $table->string('ktp_image')->nullable();
            
            // Bisa tambah kolom lain sesuai kebutuhan nanti
            // $table->enum('gender', ['L', 'P'])->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_data');
    }
};