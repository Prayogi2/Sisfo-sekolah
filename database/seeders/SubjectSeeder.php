<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Daftar mata pelajaran MIS Nurul Falaq sesuai kurikulum yang berjalan.
 */
class SubjectSeeder extends Seeder
{
    public const SUBJECTS = [
        ['code' => 'BIND', 'name' => 'B. Indonesia'],
        ['code' => 'MTK', 'name' => 'Matematika'],
        ['code' => 'IPAS', 'name' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)'],
        ['code' => 'PP', 'name' => 'Pendidikan Pancasila'],
        ['code' => 'AAM', 'name' => 'Aksara Arab Melayu'],
        ['code' => 'TQ', 'name' => "Tahfidz Qur'an"],
        ['code' => 'SR', 'name' => 'Seni Rupa'],
        ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani, Olahraga dan Kesehatan (PJOK)'],
        ['code' => 'BING', 'name' => 'B. Inggris'],
        ['code' => 'BJPN', 'name' => 'B. Jepang'],
        ['code' => 'SMP', 'name' => 'Sempoa'],
        ['code' => 'BARB', 'name' => 'B. Arab'],
        ['code' => 'QH', 'name' => "Alqur'an Hadis"],
        ['code' => 'AKD', 'name' => 'Akidah Akhlak'],
        ['code' => 'FKH', 'name' => 'Fikih'],
        ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam (SKI)'],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $subject) {
            Subject::updateOrCreate(['code' => $subject['code']], $subject);
        }
    }
}
