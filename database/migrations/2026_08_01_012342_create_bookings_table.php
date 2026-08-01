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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke User (Penyewa)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Relasi ke Mobil yang disewa
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            
            // Opsional: Relasi ke Driver jika menggunakan jasa sopir
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');

            // Tanggal dan durasi sewa
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');

            // Finansial Sewa Mobil
            $table->decimal('price_per_day', 12, 2);

            // Pilihan & Finansial Jasa Driver (Dipisah)
            $table->boolean('with_driver')->default(false);
            $table->decimal('driver_fee_per_day', 12, 2)->default(0);
            $table->decimal('total_driver_fee', 12, 2)->default(0);

            // Total Keseluruhan (Mobil + Driver)
            $table->decimal('total_price', 12, 2);

            // Status Booking & Pembayaran
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            
            // 👇 Metode Pembayaran (QRIS Statis atau Bayar di Tempat/COD)
            $table->enum('payment_method', ['qris', 'cod'])->nullable();

            // Catatan tambahan dari penyewa
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};