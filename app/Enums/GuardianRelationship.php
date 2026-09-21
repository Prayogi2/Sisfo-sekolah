<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Father = 'ayah';
    case Mother = 'ibu';
    case Guardian = 'wali';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Father => 'Ayah',
            self::Mother => 'Ibu',
            self::Guardian => 'Wali',
        };
    }
}
