<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->after('payment_method');
            $table->text('qris_url')->nullable()->after('midtrans_order_id');
            $table->timestamp('payment_expired_at')->nullable()->after('qris_url');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['midtrans_order_id', 'qris_url', 'payment_expired_at']);
        });
    }
};