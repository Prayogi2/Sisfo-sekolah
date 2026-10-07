<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            /*
             * Nama & peran pelaku disimpan sebagai salinan, bukan hanya
             * relasi, supaya jejaknya tetap terbaca kalau akunnya dihapus.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_role', 20)->nullable();

            /** Admin yang sedang "masuk sebagai" pengguna lain, bila ada. */
            $table->foreignId('impersonator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action');
            $table->string('description');

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            $table->string('method', 10);
            $table->string('url', 2048);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['user_role', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
