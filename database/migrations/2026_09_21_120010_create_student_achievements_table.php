<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 30);
            $table->string('title');
            $table->string('event')->nullable();
            $table->string('level')->nullable();
            $table->string('achievement')->nullable();
            $table->text('benefit')->nullable();
            $table->text('description')->nullable();
            $table->date('achieved_at')->nullable();
            $table->string('evidence_path')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
