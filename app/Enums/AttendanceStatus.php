<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'hadir';
    case Late = 'telat';
    case Excused = 'izin';
    case Absent = 'alpa';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Telat',
            self::Excused => 'Izin/Sakit',
            self::Absent => 'Alpa',
        };
    }

    /**
     * Warna badge Bootstrap untuk status ini.
     */
    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late => 'warning',
            self::Excused => 'info',
            self::Absent => 'danger',
        };
    }

    /**
     * Siswa dihitung "masuk" sekolah bila hadir atau datang terlambat.
     */
    public function isInSchool(): bool
    {
        return in_array($this, [self::Present, self::Late], true);
    }
}
