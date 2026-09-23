<?php

namespace App\Enums;

enum ResidenceType: string
{
    case WithParents = 'orang_tua';
    case WithGuardian = 'wali';
    case Dormitory = 'asrama_pondok';
    case Rented = 'kos_kontrak';
    case Orphanage = 'panti_asuhan';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::WithParents => 'Bersama Orang Tua',
            self::WithGuardian => 'Bersama Wali',
            self::Dormitory => 'Asrama / Pondok',
            self::Rented => 'Kos / Kontrak',
            self::Orphanage => 'Panti Asuhan',
        };
    }
}
