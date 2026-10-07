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

    /**
     * Bentuk array agar hasil bisa di-flash ke session yang diserialisasi sebagai JSON.
     *
     * @return array{imported: int, updated: int, errors: list<string>}
     */
    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'errors' => $this->errors,
        ];
    }
}
