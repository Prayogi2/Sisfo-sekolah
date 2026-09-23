<?php

namespace App\Enums;

enum AnnouncementCategory: string
{
    case Info = 'info';
    case Announcement = 'pengumuman';
    case Warning = 'peringatan';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info Umum',
            self::Announcement => 'Pengumuman Penting',
            self::Warning => 'Peringatan',
        };
    }

    /**
     * Warna Bootstrap untuk badge & ikon kategori ini.
     */
    public function color(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Announcement => 'primary',
            self::Warning => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'bi-info-circle-fill',
            self::Announcement => 'bi-megaphone-fill',
            self::Warning => 'bi-exclamation-triangle-fill',
        };
    }
}
