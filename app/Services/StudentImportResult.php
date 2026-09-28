<?php

namespace App\Services;

readonly class StudentImportResult
{
    /**
     * @param  list<string>  $errors  Pesan per baris yang gagal, mis. "Baris 5: NISN sudah terdaftar."
     */
    public function __construct(
        public int $imported,
        public array $errors,
        public int $updated = 0,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
