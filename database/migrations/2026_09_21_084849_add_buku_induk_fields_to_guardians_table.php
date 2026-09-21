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
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('nik')->nullable()->unique()->after('name');
            $table->string('family_card_number')->nullable()->after('nik');
            $table->string('birth_place')->nullable()->after('family_card_number');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->nullable()->after('birth_date');
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable()->after('religion');
            $table->string('last_education')->nullable()->after('occupation');
            $table->text('address')->nullable()->after('last_education');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'family_card_number',
                'birth_place',
                'birth_date',
                'religion',
                'blood_type',
                'last_education',
                'address',
            ]);
        });
    }
};
