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
        Schema::create('student_academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();

            // Riwayat pendidikan sebelumnya (asal TK/PAUD)
            $table->string('kindergarten_origin')->nullable();
            $table->string('kindergarten_certificate_number')->nullable();
            $table->date('kindergarten_certificate_date')->nullable();

            // Status masuk & keluar/pindah
            $table->string('entry_status')->nullable();
            $table->date('entry_date')->nullable();
            $table->date('transfer_out_date')->nullable();
            $table->text('transfer_out_reason')->nullable();
            $table->date('exit_date')->nullable();
            $table->text('exit_reason')->nullable();

            // Data kelulusan
            $table->enum('graduation_status', ['belum_lulus', 'lulus', 'tidak_lulus'])->default('belum_lulus');
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('graduation_certificate_number')->nullable();
            $table->date('graduation_certificate_date')->nullable();
            $table->string('continued_to')->nullable();
            $table->text('graduation_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academic_records');
    }
};
