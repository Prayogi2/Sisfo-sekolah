<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case BiologicalChild = 'anak_kandung';
    case StepChild = 'anak_tiri';
    case AdoptedChild = 'anak_angkat';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::BiologicalChild => 'Anak Kandung',
            self::StepChild => 'Anak Tiri',
            self::AdoptedChild => 'Anak Angkat',
        };
    }
}
