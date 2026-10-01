<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izin/sakit kini diajukan lewat akun siswa. Data orang tua di buku
     * induk belum tentu ada, jadi pengaju boleh ditulis langsung namanya.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable()->change();
            $table->string('applicant_name', 100)->nullable()->after('guardian_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('applicant_name');
        });
    }
};
