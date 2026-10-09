<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('guardian_id')->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('feedbacks')->whereNull('guardian_id')->orWhereNotNull('student_id')->exists()) {
            throw new RuntimeException('Student feedback exists; migrate it before reverting this schema change.');
        }

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->foreignId('guardian_id')->nullable(false)->change();
        });
    }
};
