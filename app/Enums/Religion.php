<?php

namespace App\Enums;

enum Religion: string
{
    case Islam = 'islam';
    case Christian = 'kristen';
    case Catholic = 'katolik';
    case Hindu = 'hindu';
    case Buddhist = 'buddha';
    case Confucian = 'konghucu';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Islam => 'Islam',
            self::Christian => 'Kristen',
            self::Catholic => 'Katolik',
            self::Hindu => 'Hindu',
            self::Buddhist => 'Buddha',
            self::Confucian => 'Konghucu',
        };
    }
}
