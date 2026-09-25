<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biodata guru. Nama, jenis kelamin, email & No. WhatsApp (phone) sudah
     * ada; `address` yang sudah ada dipakai sebagai alamat jalan/dusun.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('nip');
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('village')->nullable()->after('address');
            $table->string('district')->nullable()->after('village');
            $table->string('province')->nullable()->after('district');
            $table->string('last_education')->nullable()->after('province');
            $table->string('blood_type', 2)->nullable()->after('last_education');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'birth_place', 'birth_date', 'village', 'district', 'province', 'last_education', 'blood_type']);
        });
    }
};
