<?php

namespace App\Enums;

enum GraduationStatus: string
{
    case NotYetGraduated = 'belum_lulus';
    case Graduated = 'lulus';
    case NotGraduated = 'tidak_lulus';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotYetGraduated => 'Belum Lulus',
            self::Graduated => 'Lulus',
            self::NotGraduated => 'Tidak Lulus',
        };
    }
}
