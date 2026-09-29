<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case Furniture = 'mebelair';
    case LearningSupplies = 'perlengkapan_pembelajaran';
    case Electronics = 'elektronik';
    case Cleaning = 'kebersihan';
    case Decoration = 'dekorasi_informasi';

    /**
     * Label bahasa Indonesia untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Furniture => 'Mebelair',
            self::LearningSupplies => 'Perlengkapan Pembelajaran',
            self::Electronics => 'Elektronik',
            self::Cleaning => 'Perlengkapan Kebersihan',
            self::Decoration => 'Dekorasi & Informasi',
        };
    }

    /**
     * Daftar barang standar tiap kelas (dari revisi form inventaris sekolah).
     *
     * @return list<string>
     */
    public function defaultItems(): array
    {
        return match ($this) {
            self::Furniture => ['Meja Belajar Siswa', 'Kursi Belajar Siswa', 'Kursi Guru', 'Meja Guru', 'Lemari', 'Rak Buku', 'Papan Tulis', 'Loker / Lemari Penyimpanan'],
            self::LearningSupplies => ['Spidol', 'Penghapus', 'Penggaris', 'Alat Peraga', 'Globe / Peta', 'Media Pembelajaran'],
            self::Electronics => ['Kipas Angin', 'AC', 'Lampu', 'Stop Kontak', 'Cok Sambung'],
            self::Cleaning => ['Sapu Ijuk', 'Ember', 'Pel', 'Keranjang Sampah', 'Serokan Sampah', 'Rak Sepatu'],
            self::Decoration => ['Jam Dinding', 'Hiasan Kelas', 'Poster Presiden & Wakil Presiden', 'Struktur Kelas', 'Papan Prestasi'],
        };
    }
}
