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
        Schema::table('personal_data', function (Blueprint $table) {
            $table->string('sim_number')->nullable()->after('ktp_image');
            $table->string('sim_type')->nullable()->after('sim_number');
            $table->date('sim_expired_date')->nullable()->after('sim_type');
            $table->string('sim_image')->nullable()->after('sim_expired_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_data', function (Blueprint $table) {
            $table->dropColumn([
                'sim_number',
                'sim_type',
                'sim_expired_date',
                'sim_image'
            ]);
        });
    }
};