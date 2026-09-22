<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuis hanya bisa dikerjakan saat guru mapel membukanya di jam pelajaran.
     * Default tertutup supaya kuis tidak bisa dicicil siswa di luar jam.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_open')->default(false)->after('is_published');
            $table->timestamp('opened_at')->nullable()->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['is_open', 'opened_at']);
        });
    }
};
