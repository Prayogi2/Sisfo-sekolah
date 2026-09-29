<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan WhatsApp ke orang tua gagal/dilewati, untuk ringkasan & detail
     * notifikasi (mis. "Nomor WhatsApp orang tua belum terisi").
     */
    public function up(): void
    {
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->string('whatsapp_error')->nullable()->after('whatsapp_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->dropColumn('whatsapp_error');
        });
    }
};
