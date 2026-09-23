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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->enum('residence_type', ['orang_tua', 'wali', 'asrama_pondok', 'kos_kontrak', 'panti_asuhan'])->nullable()->after('postal_code');
            $table->enum('transportation', ['jalan_kaki', 'sepeda', 'sepeda_motor', 'mobil', 'angkutan_umum', 'angkutan_sekolah'])->nullable()->after('residence_type');
            $table->unsignedSmallInteger('distance_km')->nullable()->after('transportation');
            $table->unsignedSmallInteger('travel_duration_minutes')->nullable()->after('distance_km');
            $table->dropColumn('family_card_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Data yang pernah tersimpan di family_card_number tidak dipulihkan,
     * kolomnya cuma dibuat kosong kembali.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['residence_type', 'transportation', 'distance_km', 'travel_duration_minutes']);
            $table->string('family_card_number')->nullable()->after('nik');
        });
    }
};
