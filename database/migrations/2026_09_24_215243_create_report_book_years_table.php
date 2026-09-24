<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baris "Tahun Ajaran" & "Naik ke Kelas" di tabel nilai buku induk,
     * satu baris per tingkat kelas (1–6) tiap siswa.
     */
    public function up(): void
    {
        Schema::create('report_book_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->string('academic_year', 20)->nullable();
            $table->string('promoted_to', 50)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'grade_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_book_years');
    }
};
