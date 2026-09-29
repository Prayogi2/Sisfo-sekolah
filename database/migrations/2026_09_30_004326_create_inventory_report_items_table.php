<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Isi satu laporan kondisi per barang. Nama & kategori barang disalin
     * supaya riwayat tetap utuh walau barangnya kemudian dihapus admin.
     */
    public function up(): void
    {
        Schema::create('inventory_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);
            $table->string('name', 100);
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->string('photo_path')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_report_items');
    }
};
