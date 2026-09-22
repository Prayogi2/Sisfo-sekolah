<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity', 20);
            $table->string('title');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->date('occurred_at');
            $table->string('evidence_path')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_violations');
    }
};
