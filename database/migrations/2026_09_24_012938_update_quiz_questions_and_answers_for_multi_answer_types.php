<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * correct_answer/answer jadi array JSON supaya satu soal bisa punya
     * lebih dari satu kunci jawaban (pilihan ganda kompleks). Kondisi
     * WHERE ... NOT LIKE '[%' membuat migrasi ini aman dijalankan dua kali
     * pada data yang sudah pernah dikonversi sebelumnya.
     */
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('type')->default('single')->change();
            $table->string('correct_answer', 50)->change();
        });

        DB::table('quiz_questions')->where('type', 'multiple_choice')->update(['type' => 'single']);
        DB::table('quiz_questions')
            ->whereNotNull('correct_answer')
            ->where('correct_answer', 'not like', '[%')
            ->update(['correct_answer' => DB::raw('JSON_ARRAY(correct_answer)')]);

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->string('answer', 50)->nullable()->change();
        });

        DB::table('quiz_answers')
            ->whereNotNull('answer')
            ->where('answer', 'not like', '[%')
            ->update(['answer' => DB::raw('JSON_ARRAY(answer)')]);
    }

    /**
     * Reverse the migrations.
     *
     * Mengecilkan kunci jawaban kompleks (>1 jawaban) jadi hanya elemen
     * pertama; data jawaban tambahan tidak dipulihkan.
     */
    public function down(): void
    {
        DB::table('quiz_questions')
            ->whereNotNull('correct_answer')
            ->update(['correct_answer' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(correct_answer, '$[0]'))")]);
        DB::table('quiz_questions')->where('type', 'single')->update(['type' => 'multiple_choice']);

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('correct_answer', 1)->change();
            $table->string('type')->default('multiple_choice')->change();
        });

        DB::table('quiz_answers')
            ->whereNotNull('answer')
            ->update(['answer' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(answer, '$[0]'))")]);

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->string('answer', 1)->nullable()->change();
        });
    }
};
