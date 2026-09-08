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
        Schema::create('user_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('token');
            $table->string('device_id')->nullable();
            $table->string('platform')->nullable(); // android, ios, web, dll
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Satu token fisik cuma boleh nyantol ke 1 baris.
            $table->unique('token');

            // 🟢 BARU: satu device (user_id + device_id) cuma boleh punya
            // 1 baris token aktif. Ini kunci untuk mencegah device yang
            // sama numpuk banyak baris tiap kali Firebase kasih token
            // baru -- baris lama akan di-UPDATE (bukan bikin baris baru)
            // lewat updateOrCreate() di controller.
            //
            // Catatan: MySQL memperbolehkan banyak baris dengan NULL di
            // kolom unique (NULL dianggap tidak sama dengan NULL lain),
            // jadi constraint ini otomatis "tidak aktif" untuk baris yang
            // device_id-nya null -- device seperti itu tetap fallback ke
            // dedupe berdasarkan unique('token') saja.
            $table->unique(['user_id', 'device_id']);

            // Query "ambil semua token milik user X" bakal sering dipakai
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};