<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * KKM (kriteria ketuntasan minimal). Nilai akhir di bawah angka ini
 * ditandai perlu remedial di laporan nilai.
 */
class GradeSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('grade_passing_score', 70);
    }
}
