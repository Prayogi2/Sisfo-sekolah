<?php

namespace App\Enums;

enum AnnouncementTarget: string
{
    case AllStudents = 'semua';
    case Classroom = 'kelas';
    case SpecificStudents = 'siswa';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::AllStudents => 'Semua Siswa',
            self::Classroom => 'Per Kelas',
            self::SpecificStudents => 'Siswa Tertentu',
        };
    }
}
