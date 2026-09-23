<?php

namespace App\Services;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\FamilyStatus;
use App\Enums\GraduationStatus;
use App\Enums\GuardianRelationship;
use App\Enums\Religion;
use App\Enums\ResidenceType;
use App\Enums\TransportationMode;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Menyimpan bagian Buku Induk di luar identitas siswa (profil, foto,
 * riwayat pendidikan, data ayah & ibu). Dipakai saat tambah siswa maupun
 * edit Buku Induk, dan semua field-nya opsional agar bisa dilengkapi menyusul.
 */
class StudentRecordWriter
{
    private const PROFILE_FIELDS = [
        'nickname', 'nik', 'religion', 'family_status',
        'birth_order', 'siblings_count', 'weight_kg', 'height_cm', 'blood_type',
        'street_address', 'hamlet', 'village', 'district', 'regency', 'province', 'postal_code',
        'residence_type', 'transportation', 'distance_km', 'travel_duration_minutes',
    ];

    private const ACADEMIC_FIELDS = [
        // A. Pendidikan sebelumnya
        'kindergarten_origin', 'kindergarten_address', 'kindergarten_npsn',
        'kindergarten_certificate_number', 'kindergarten_certificate_date',
        // B. Status peserta didik
        'entry_status', 'entry_year', 'entry_date', 'entry_classroom',
        // C. Lulus
        'graduation_status', 'graduation_year', 'graduation_certificate_number', 'graduation_certificate_date',
        'graduation_skl_number', 'continued_to', 'continued_to_district', 'continued_to_province', 'graduation_notes',
        // D. Meninggalkan sekolah (pindah)
        'transfer_out_letter_number', 'transfer_out_date', 'transfer_out_classroom', 'transfer_out_reason',
        'transfer_out_nsm', 'transfer_out_npsn', 'transfer_out_village', 'transfer_out_district', 'transfer_out_province',
        // E. Putus sekolah / dropout
        'exit_date', 'exit_classroom', 'exit_reason',
    ];

    private const GUARDIAN_FIELDS = [
        'name', 'gender', 'nik', 'family_card_number', 'birth_place', 'birth_date', 'religion',
        'blood_type', 'last_education', 'occupation', 'monthly_income', 'phone', 'address',
    ];

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(?Student $student = null): array
    {
        $rules = [
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:30', Rule::unique('student_profiles', 'nik')->ignore($student?->profile?->id)],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'family_status' => ['nullable', Rule::enum(FamilyStatus::class)],
            'birth_order' => ['nullable', 'integer', 'min:1'],
            'siblings_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'integer', 'min:0'],
            'height_cm' => ['nullable', 'integer', 'min:0'],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'street_address' => ['nullable', 'string'],
            'hamlet' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'regency' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'residence_type' => ['nullable', Rule::enum(ResidenceType::class)],
            'transportation' => ['nullable', Rule::enum(TransportationMode::class)],
            'distance_km' => ['nullable', 'integer', 'min:0'],
            'travel_duration_minutes' => ['nullable', 'integer', 'min:0'],

            // A. Pendidikan sebelumnya
            'kindergarten_origin' => ['nullable', 'string', 'max:255'],
            'kindergarten_address' => ['nullable', 'string', 'max:255'],
            'kindergarten_npsn' => ['nullable', 'string', 'max:50'],
            'kindergarten_certificate_number' => ['nullable', 'string', 'max:100'],
            'kindergarten_certificate_date' => ['nullable', 'date'],

            // B. Status peserta didik
            'entry_status' => ['nullable', 'string', 'max:100'],
            'entry_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'entry_date' => ['nullable', 'date'],
            'entry_classroom' => ['nullable', 'string', 'max:100'],

            // C. Lulus
            'graduation_status' => ['nullable', Rule::enum(GraduationStatus::class)],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'graduation_certificate_number' => ['nullable', 'string', 'max:100'],
            'graduation_certificate_date' => ['nullable', 'date'],
            'graduation_skl_number' => ['nullable', 'string', 'max:100'],
            'continued_to' => ['nullable', 'string', 'max:255'],
            'continued_to_district' => ['nullable', 'string', 'max:255'],
            'continued_to_province' => ['nullable', 'string', 'max:255'],
            'graduation_notes' => ['nullable', 'string'],

            // D. Meninggalkan sekolah (pindah)
            'transfer_out_letter_number' => ['nullable', 'string', 'max:100'],
            'transfer_out_date' => ['nullable', 'date'],
            'transfer_out_classroom' => ['nullable', 'string', 'max:100'],
            'transfer_out_reason' => ['nullable', 'string'],
            'transfer_out_nsm' => ['nullable', 'string', 'max:50'],
            'transfer_out_npsn' => ['nullable', 'string', 'max:50'],
            'transfer_out_village' => ['nullable', 'string', 'max:255'],
            'transfer_out_district' => ['nullable', 'string', 'max:255'],
            'transfer_out_province' => ['nullable', 'string', 'max:255'],

            // E. Putus sekolah / dropout
            'exit_date' => ['nullable', 'date'],
            'exit_classroom' => ['nullable', 'string', 'max:100'],
            'exit_reason' => ['nullable', 'string'],
        ];

        foreach (['father', 'mother'] as $prefix) {
            $rules += [
                $prefix.'_name' => ['nullable', 'string', 'max:255'],
                $prefix.'_gender' => ['nullable', 'in:L,P'],
                $prefix.'_nik' => ['nullable', 'string', 'max:30'],
                $prefix.'_family_card_number' => ['nullable', 'string', 'max:30'],
                $prefix.'_birth_place' => ['nullable', 'string', 'max:255'],
                $prefix.'_birth_date' => ['nullable', 'date'],
                $prefix.'_religion' => ['nullable', Rule::enum(Religion::class)],
                $prefix.'_blood_type' => ['nullable', Rule::enum(BloodType::class)],
                $prefix.'_last_education' => ['nullable', Rule::enum(EducationLevel::class)],
                $prefix.'_occupation' => ['nullable', 'string', 'max:255'],
                $prefix.'_monthly_income' => ['nullable', 'string', 'max:100'],
                $prefix.'_phone' => ['nullable', 'string', 'max:30'],
                $prefix.'_address' => ['nullable', 'string'],
            ];
        }

        return $rules;
    }

    /**
     * Profil & riwayat pendidikan baru dibuat bila ada isinya, supaya siswa
     * yang datanya belum dilengkapi tetap tampil "belum lengkap" di Buku Induk.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(Student $student, array $data, ?UploadedFile $photo = null): void
    {
        $student->loadMissing(['profile', 'academicRecord']);

        $profileData = collect($data)->only(self::PROFILE_FIELDS)->all();

        if ($student->profile || $this->hasValues($profileData) || $photo) {
            $student->profile()->updateOrCreate(['student_id' => $student->id], $profileData);
        }

        if ($photo) {
            if ($student->profile?->photo_path) {
                Storage::disk('public')->delete($student->profile->photo_path);
            }

            $student->profile()->update(['photo_path' => $photo->store('student-photos', 'public')]);
        }

        $academicData = collect($data)->only(self::ACADEMIC_FIELDS)->all();

        if ($student->academicRecord || $this->hasValues($academicData)) {
            $student->academicRecord()->updateOrCreate(['student_id' => $student->id], $academicData);
        }

        foreach ([GuardianRelationship::Father, GuardianRelationship::Mother] as $relationship) {
            $prefix = $relationship === GuardianRelationship::Father ? 'father' : 'mother';
            $guardian = $student->guardians()->where('relationship', $relationship)->first();
            $guardianData = collect(self::GUARDIAN_FIELDS)
                ->mapWithKeys(fn (string $field) => [$field => $data[$prefix.'_'.$field] ?? null])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all();

            if ($guardian) {
                $guardian->update($guardianData);
            } elseif ($guardianData !== []) {
                $student->guardians()->create($guardianData + ['relationship' => $relationship]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function hasValues(array $attributes): bool
    {
        return collect($attributes)->contains(fn ($value) => $value !== null && $value !== '');
    }
}
