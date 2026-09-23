<?php

namespace App\Enums;

enum TransportationMode: string
{
    case Walking = 'jalan_kaki';
    case Bicycle = 'sepeda';
    case Motorcycle = 'sepeda_motor';
    case Car = 'mobil';
    case PublicTransport = 'angkutan_umum';
    case SchoolBus = 'angkutan_sekolah';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Walking => 'Jalan Kaki',
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Sepeda Motor',
            self::Car => 'Mobil / Antar Jemput',
            self::PublicTransport => 'Angkutan Umum',
            self::SchoolBus => 'Angkutan Sekolah',
        };
    }
}
