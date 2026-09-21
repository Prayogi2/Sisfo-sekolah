<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('multiple_choice');
            $table->text('question');
            $table->json('options');
            $table->string('correct_answer', 1);
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['subject_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
