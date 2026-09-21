<?php

namespace Database\Seeders;

use App\Models\AttendanceSchedule;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Jadwal masuk & pulang sesuai BRIEF NURFA.ID.docx:
 * - Masuk Senin-Sabtu jam 07.15 WIB (semua kelas).
 * - Pulang Senin-Kamis: kelas 1-2 jam 13.40, kelas 3-6 jam 14.20.
 * - Pulang Jumat: semua kelas (1-6) jam 10.40.
 * - Pulang Sabtu: kelas 1-2 jam 09.50, kelas 3-6 jam 10.40.
 */
class AttendanceScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $checkIn = '07:15:00';

        $rules = [
            // [days, grade_min, grade_max, check_out]
            [[1, 2, 3, 4], 1, 2, '13:40:00'],
            [[1, 2, 3, 4], 3, 6, '14:20:00'],
            [[5], 1, 6, '10:40:00'],
            [[6], 1, 2, '09:50:00'],
            [[6], 3, 6, '10:40:00'],
        ];

        foreach ($rules as [$days, $gradeMin, $gradeMax, $checkOut]) {
            foreach ($days as $day) {
                AttendanceSchedule::updateOrCreate(
                    ['day_of_week' => $day, 'grade_min' => $gradeMin, 'grade_max' => $gradeMax],
                    ['check_in_time' => $checkIn, 'check_out_time' => $checkOut]
                );
            }
        }

        // Default: siswa telat tetap boleh scan (dicatat Telat). Admin bisa
        // aktifkan agar scan telat diblokir & langsung tercatat Alpa.
        Setting::set('late_scan_blocking_enabled', false);
    }
}
