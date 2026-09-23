<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama, TTL, jenis kelamin, NIS & NISN sudah ada di tabel students, dan
     * no. seri ijazah sudah ada sebagai graduation_certificate_number —
     * yang benar-benar belum ada hanya no. seri rapor & no. ujian.
     */
    public function up(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->string('report_book_serial_number', 100)->nullable()->after('entry_date');
            $table->string('exam_number', 50)->nullable()->after('graduation_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->dropColumn(['report_book_serial_number', 'exam_number']);
        });
    }
};
