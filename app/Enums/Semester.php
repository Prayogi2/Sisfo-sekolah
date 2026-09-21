<?php

namespace App\Enums;

enum Semester: string
{
    case Odd = 'ganjil';
    case Even = 'genap';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Odd => 'Ganjil',
            self::Even => 'Genap',
        };
    }

    /**
     * Semester berjalan. Tahun ajaran dimulai Juli, jadi Juli-Desember
     * masuk semester ganjil dan Januari-Juni semester genap.
     */
    public static function current(): self
    {
        return now()->month >= 7 ? self::Odd : self::Even;
    }
}
