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
        Schema::create('attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week'); // ISO-8601: 1 = Senin ... 7 = Minggu
            $table->unsignedTinyInteger('grade_min');
            $table->unsignedTinyInteger('grade_max');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->timestamps();

            $table->unique(['day_of_week', 'grade_min', 'grade_max']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_schedules');
    }
};
