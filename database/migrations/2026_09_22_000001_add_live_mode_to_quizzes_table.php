<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('mode')->default('async')->after('duration_minutes');
            $table->boolean('show_score_per_question')->default(false)->after('mode');
            $table->string('live_phase')->default('lobby')->after('opened_at');
            $table->unsignedInteger('live_question_index')->nullable()->after('live_phase');
            $table->timestamp('live_question_started_at')->nullable()->after('live_question_index');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['mode', 'show_score_per_question', 'live_phase', 'live_question_index', 'live_question_started_at']);
        });
    }
};
