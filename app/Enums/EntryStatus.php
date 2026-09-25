<?php

namespace App\Enums;

/**
 * Status peserta didik saat masuk madrasah (Buku Induk bagian B). Nilainya
 * sekaligus teks yang tersimpan di kolom entry_status, jadi data lama
 * "Peserta Didik Baru" tetap cocok tanpa perlu dikonversi.
 */
enum EntryStatus: string
{
    case NewStudent = 'Peserta Didik Baru';
    case Transfer = 'Pindahan';
    case Returning = 'Kembali Bersekolah';

    public function label(): string
    {
        return $this->value;
    }
}
