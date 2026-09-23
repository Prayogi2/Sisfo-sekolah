<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi in-app dari admin ke siswa. Tidak memakai tabel
     * `notifications` bawaan Laravel karena tabel itu sudah dipakai untuk
     * notifikasi kritik & saran ke admin.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('category');
            $table->string('target');
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
