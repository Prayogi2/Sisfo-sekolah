<?php

namespace App\Enums;

enum PromotionStatus: string
{
    case Undecided = 'belum_ditentukan';
    case Promoted = 'naik';
    case Retained = 'tinggal';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Undecided => 'Belum Ditentukan',
            self::Promoted => 'Naik Kelas',
            self::Retained => 'Tinggal Kelas',
        };
    }

    /**
     * Warna badge Bootstrap supaya status kenaikan kelas langsung terbaca
     * di tabel perkembangan akademik.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Undecided => 'bg-secondary',
            self::Promoted => 'bg-success',
            self::Retained => 'bg-danger',
        };
    }
}
