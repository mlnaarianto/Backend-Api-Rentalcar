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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            // 👇 Relasi ke tabel users dengan cascade delete
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('name'); // Contoh: Avanza, Innova Zenix Hybrid, Wuling Air EV
            $table->string('brand'); // Contoh: Toyota, Wuling
            $table->string('plate_number')->unique();

            // 👇 1. Kategori Utama Penggerak
            $table->enum('engine_type', [
                'Bensin (Gasoline)',
                'Diesel (Gasoil)',
                'Listrik (Electric / EV)',
                'Hybrid (HEV / PHEV)'
            ]);

            // 👇 2. Detail Jenis Bahan Bakar (Opsional/Boleh kosong jika murni Listrik)
            $table->string('fuel_spec')->nullable();

            $table->integer('seats');
            $table->year('year');
            $table->decimal('price_per_day', 12, 2);
            
            // 👇 Tarif driver diatur oleh perental secara fleksibel (tidak di-hardcode)
            $table->decimal('driver_price_per_day', 12, 2)->default(0);

            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['tersedia', 'disewa', 'perbaikan'])->default('tersedia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};