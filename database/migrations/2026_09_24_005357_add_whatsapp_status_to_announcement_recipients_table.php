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
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->string('whatsapp_status')->nullable()->after('read_at');
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_status', 'whatsapp_sent_at']);
        });
    }
};
