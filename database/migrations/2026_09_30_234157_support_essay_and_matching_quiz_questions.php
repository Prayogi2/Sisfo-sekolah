<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soal essay & menjodohkan butuh kunci/jawaban berupa teks panjang (bukan
     * sekadar huruf A–D), dan jawaban essay baru bernilai setelah dikoreksi
     * guru (graded_at).
     */
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('correct_answer')->nullable()->change();
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->text('answer')->nullable()->change();
            $table->timestamp('graded_at')->nullable()->after('awarded_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('graded_at');
            $table->string('answer', 50)->nullable()->change();
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('correct_answer', 50)->change();
        });
    }
};
