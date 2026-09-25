<?php

namespace App\Http\Requests\Teacher\Concerns;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Models\Teacher;
use Illuminate\Validation\Rule;

/**
 * Aturan biodata guru yang sama untuk form Tambah & Edit Guru.
 */
trait ValidatesTeacherProfile
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function profileRules(?Teacher $teacher = null): array
    {
        return [
            'nik' => ['nullable', 'digits:16', Rule::unique('teachers', 'nik')->ignore($teacher)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'last_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function profileMessages(): array
    {
        return [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah dipakai guru lain.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
