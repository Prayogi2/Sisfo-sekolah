<?php

namespace App\Enums;

enum EducationLevel: string
{
    case None = 'tidak_sekolah';
    case ElementarySchool = 'sd';
    case JuniorHighSchool = 'smp';
    case SeniorHighSchool = 'sma';
    case Diploma = 'diploma';
    case Bachelor = 's1';
    case Master = 's2';
    case Doctorate = 's3';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'Tidak Sekolah',
            self::ElementarySchool => 'SD/Sederajat',
            self::JuniorHighSchool => 'SMP/Sederajat',
            self::SeniorHighSchool => 'SMA/Sederajat',
            self::Diploma => 'Diploma',
            self::Bachelor => 'S1',
            self::Master => 'S2',
            self::Doctorate => 'S3',
        };
    }
}
