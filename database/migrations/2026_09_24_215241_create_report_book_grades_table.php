<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai "Laporan Hasil Belajar" di buku induk: satu baris per mata
     * pelajaran siswa, dengan satu kolom nilai untuk tiap Kelas 1–6 ×
     * Semester 1–2 (persis kolom di form fisik). Terpisah dari tabel
     * `grades` (nilai harian guru) karena diisi admin dari rapor.
     */
    public function up(): void
    {
        Schema::create('report_book_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('subject_name', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);

            foreach (range(1, 6) as $gradeLevel) {
                foreach ([1, 2] as $semester) {
                    $table->decimal("grade_{$gradeLevel}_semester_{$semester}", 5, 2)->nullable();
                }
            }

            $table->timestamps();

            $table->index(['student_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_book_grades');
    }
};
