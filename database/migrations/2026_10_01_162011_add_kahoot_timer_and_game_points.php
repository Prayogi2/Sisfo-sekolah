<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode Kahoot: batas waktu tiap soal, lama siswa menjawab, dan poin
     * permainan (bonus kecepatan) untuk papan peringkat. Nilai kuis/rapor
     * tetap memakai awarded_points, tidak terpengaruh kecepatan.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedSmallInteger('question_seconds')->default(30)->after('duration_minutes');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->unsignedInteger('response_ms')->nullable()->after('awarded_points');
            $table->unsignedInteger('game_points')->default(0)->after('response_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn(['response_ms', 'game_points']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('question_seconds');
        });
    }
};
