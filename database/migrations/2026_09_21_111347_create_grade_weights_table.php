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
        Schema::create('grade_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->unsignedTinyInteger('assignment_weight');
            $table->unsignedTinyInteger('quiz_weight');
            $table->unsignedTinyInteger('midterm_weight');
            $table->unsignedTinyInteger('final_weight');
            $table->timestamps();

            $table->unique(['subject_id', 'academic_year', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_weights');
    }
};
