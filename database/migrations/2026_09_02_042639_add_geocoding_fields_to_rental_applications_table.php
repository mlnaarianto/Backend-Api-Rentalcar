<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('business_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            
            // opsional: hasil formatted address dari geocoding API, 
            // biar gak perlu re-geocode setiap kali ditampilkan
            $table->string('formatted_address')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'formatted_address']);
        });
    }
};