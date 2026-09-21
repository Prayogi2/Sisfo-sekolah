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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('academic_year');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->unsignedTinyInteger('assignment_score')->nullable();
            $table->unsignedTinyInteger('quiz_score')->nullable();
            $table->unsignedTinyInteger('midterm_score')->nullable();
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
