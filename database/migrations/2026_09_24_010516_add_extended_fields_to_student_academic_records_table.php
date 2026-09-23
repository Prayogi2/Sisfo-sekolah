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
        Schema::table('student_academic_records', function (Blueprint $table) {
            // A. Pendidikan sebelumnya
            $table->string('kindergarten_address')->nullable()->after('kindergarten_origin');
            $table->string('kindergarten_npsn')->nullable()->after('kindergarten_address');

            // B. Status peserta didik
            $table->unsignedSmallInteger('entry_year')->nullable()->after('entry_status');
            $table->string('entry_classroom')->nullable()->after('entry_date');

            // C. Lulus
            $table->string('graduation_skl_number')->nullable()->after('graduation_certificate_number');
            $table->string('continued_to_district')->nullable()->after('continued_to');
            $table->string('continued_to_province')->nullable()->after('continued_to_district');

            // D. Meninggalkan sekolah (pindah)
            $table->string('transfer_out_letter_number')->nullable()->after('transfer_out_date');
            $table->string('transfer_out_classroom')->nullable()->after('transfer_out_letter_number');
            $table->string('transfer_out_nsm')->nullable()->after('transfer_out_reason');
            $table->string('transfer_out_npsn')->nullable()->after('transfer_out_nsm');
            $table->string('transfer_out_village')->nullable()->after('transfer_out_npsn');
            $table->string('transfer_out_district')->nullable()->after('transfer_out_village');
            $table->string('transfer_out_province')->nullable()->after('transfer_out_district');

            // E. Putus sekolah / dropout
            $table->string('exit_classroom')->nullable()->after('exit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_academic_records', function (Blueprint $table) {
            $table->dropColumn([
                'kindergarten_address', 'kindergarten_npsn',
                'entry_year', 'entry_classroom',
                'graduation_skl_number', 'continued_to_district', 'continued_to_province',
                'transfer_out_letter_number', 'transfer_out_classroom', 'transfer_out_nsm',
                'transfer_out_npsn', 'transfer_out_village', 'transfer_out_district', 'transfer_out_province',
                'exit_classroom',
            ]);
        });
    }
};
