<?php

namespace App\Services;

/**
 * Ringkasan hasil import siswa ke satu kelas.
 */
readonly class ClassroomImportResult
{
    /**
     * @param  list<string>  $skipped  Baris yang tidak diproses karena siswanya sudah di kelas ini.
     * @param  list<string>  $errors  Baris yang gagal beserta alasannya, mis. "Baris 5: NISN 1234567890 tidak ditemukan."
     */
    public function __construct(
        public string $classroomName,
        public int $placed,
        public array $skipped,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Bentuk array agar hasil bisa di-flash ke session yang diserialisasi sebagai JSON.
     *
     * @return array{classroomName: string, placed: int, skipped: list<string>, errors: list<string>}
     */
    public function toArray(): array
    {
        return [
            'classroomName' => $this->classroomName,
            'placed' => $this->placed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
