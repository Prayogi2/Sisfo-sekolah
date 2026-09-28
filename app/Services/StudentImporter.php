<?php

namespace App\Services;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EntryStatus;
use App\Enums\FamilyStatus;
use App\Enums\GraduationStatus;
use App\Enums\Religion;
use App\Enums\ResidenceType;
use App\Enums\StudentStatus;
use App\Enums\TransportationMode;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor massal Data Siswa dari file Excel/CSV, sekaligus bagian Buku Induk
 * (profil, data ayah/ibu, riwayat pendidikan A-E). Tiap baris divalidasi &
 * disimpan satu per satu (bukan semua-atau-tidak-sama-sekali), supaya baris
 * yang salah dilaporkan tapi tidak menggagalkan baris lain yang benar.
 *
 * Baris dengan NISN yang sudah terdaftar TIDAK dianggap gagal — siswanya
 * diperbarui (dilengkapi/ditimpa) alih-alih membuat duplikat, supaya file
 * yang sama juga bisa dipakai untuk melengkapi Buku Induk siswa yang sudah
 * ada sebelumnya.
 */
class StudentImporter
{
    private const REQUIRED_COLUMNS = ['nisn', 'nis', 'name', 'gender'];

    private const MAX_ROWS = 1000;

    public function __construct(
        private StudentEnroller $enroller,
        private StudentRecordWriter $recordWriter,
    ) {}

    /**
     * @return list<string>
     */
    public static function templateHeaders(): array
    {
        return array_values(self::columnMap());
    }

    /**
     * @return list<string>
     */
    public static function templateExampleRow(): array
    {
        $example = self::exampleValues();

        return collect(self::columnMap())->keys()->map(fn (string $field) => $example[$field] ?? '')->all();
    }

    public function import(UploadedFile $file): StudentImportResult
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $headerRow = array_shift($rows) ?? [];

        $columnIndexes = $this->mapColumns($headerRow);

        if (count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('File berisi lebih dari '.self::MAX_ROWS.' baris data. Pecah menjadi beberapa file terlebih dahulu.');
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $sheetRowNumber = $index + 2;
            $value = fn (string $field) => isset($columnIndexes[$field]) ? trim((string) ($row[$columnIndexes[$field]] ?? '')) : null;

            $classroomName = $value('classroom');
            $classroom = $classroomName ? $this->findClassroom($classroomName) : null;

            $nisn = $value('nisn');
            $existingStudent = $nisn ? Student::query()->where('nisn', $nisn)->first() : null;

            $payload = $this->buildPayload($value, $classroom);

            $validator = Validator::make($payload, $this->rules($existingStudent), $this->messages());

            // Panggil fails() dulu untuk menjalankan validasi & mengisi
            // message bag-nya. Menambah error manual sebelum fails()
            // percuma: fails() memanggil passes(), yang selalu membuat
            // message bag baru dan membuang tambahan manual itu.
            $rulesFailed = $validator->fails();

            if ($classroomName && $classroom === null) {
                $validator->errors()->add('classroom', "Kelas \"{$classroomName}\" tidak ditemukan.");
            }

            if ($rulesFailed || ($classroomName && $classroom === null)) {
                $errors[] = "Baris {$sheetRowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            try {
                if ($existingStudent) {
                    $this->updateExisting($existingStudent, $validator->validated());
                    $updated++;
                } else {
                    $this->enroller->enroll($validator->validated());
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Baris {$sheetRowNumber}: gagal disimpan ({$e->getMessage()}).";
            }
        }

        return new StudentImportResult($created, $errors, $updated);
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function rules(?Student $existingStudent): array
    {
        return [
            'nisn' => ['required', 'digits:10', Rule::unique('students', 'nisn')->ignore($existingStudent)],
            'nis' => ['required', 'string', 'max:20', Rule::unique('students', 'nis')->ignore($existingStudent)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:L,P'],
            'classroom_id' => ['nullable', 'integer'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
        ] + $this->recordWriter->rules($existingStudent);
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'gender.in' => 'Jenis kelamin harus diisi L atau P.',
            'nisn.digits' => 'NISN harus 10 digit angka (format kolom NISN sebagai Teks di Excel agar angka 0 di depan tidak hilang).',
        ] + $this->recordWriter->messages();
    }

    /**
     * @param  callable(string): ?string  $value
     * @return array<string, mixed>
     */
    private function buildPayload(callable $value, ?Classroom $classroom): array
    {
        $payload = ['classroom_id' => $classroom?->id];

        foreach (self::genderFields() as $field) {
            $payload[$field] = $this->normalizeGender($value($field));
        }

        foreach (self::enumFields() as $field => $enumClass) {
            $payload[$field] = $this->enumValueFromLabel($enumClass, $value($field));
        }

        foreach (self::plainFields() as $field) {
            $payload[$field] = $value($field) ?: null;
        }

        return $payload;
    }

    private function updateExisting(Student $student, array $data): void
    {
        $student->update(collect($data)->only(StudentEnroller::IDENTITY_FIELDS)->filter(fn ($value) => $value !== null)->all());

        $this->recordWriter->save($student, $data);
    }

    /**
     * Field teks/angka/tanggal yang diteruskan apa adanya (tanpa terjemahan
     * enum). Diturunkan dari daftar field StudentRecordWriter supaya tidak
     * menyimpang kalau field Buku Induk berubah.
     *
     * @return list<string>
     */
    private static function plainFields(): array
    {
        $base = ['nisn', 'nis', 'name', 'birth_place', 'birth_date', 'address', 'parent_name', 'parent_phone'];

        $profile = array_diff(StudentRecordWriter::PROFILE_FIELDS, array_keys(self::enumFields()));
        $academic = array_diff(StudentRecordWriter::ACADEMIC_FIELDS, array_keys(self::enumFields()));

        $guardian = [];
        foreach (['father', 'mother'] as $prefix) {
            foreach (array_diff(StudentRecordWriter::GUARDIAN_FIELDS, ['gender', 'religion', 'blood_type', 'last_education']) as $field) {
                $guardian[] = "{$prefix}_{$field}";
            }
        }

        return [...$base, ...$profile, ...$academic, ...$guardian];
    }

    /**
     * @return array<string, class-string<\BackedEnum>>
     */
    private static function enumFields(): array
    {
        $fields = [
            'status' => StudentStatus::class,
            'religion' => Religion::class,
            'family_status' => FamilyStatus::class,
            'blood_type' => BloodType::class,
            'residence_type' => ResidenceType::class,
            'transportation' => TransportationMode::class,
            'entry_status' => EntryStatus::class,
            'graduation_status' => GraduationStatus::class,
        ];

        foreach (['father', 'mother'] as $prefix) {
            $fields["{$prefix}_religion"] = Religion::class;
            $fields["{$prefix}_blood_type"] = BloodType::class;
            $fields["{$prefix}_last_education"] = EducationLevel::class;
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    private static function genderFields(): array
    {
        return ['gender', 'father_gender', 'mother_gender'];
    }

    /**
     * Label bahasa Indonesia untuk kolom profil & riwayat pendidikan
     * (dipakai untuk field non-orang tua; field orang tua dapat label dari
     * guardianFieldLabels() dengan awalan "Ayah -"/"Ibu -").
     *
     * @return array<string, string>
     */
    private static function fieldLabels(): array
    {
        return [
            'nickname' => 'Nama Panggilan',
            'nik' => 'NIK',
            'religion' => 'Agama',
            'family_status' => 'Status dalam Keluarga',
            'birth_order' => 'Anak Ke-',
            'siblings_count' => 'Jumlah Saudara',
            'weight_kg' => 'Berat Badan (kg)',
            'height_cm' => 'Tinggi Badan (cm)',
            'blood_type' => 'Golongan Darah',
            'street_address' => 'Alamat Jalan',
            'hamlet' => 'Dusun/Gang',
            'village' => 'Desa/Kelurahan',
            'district' => 'Kecamatan',
            'regency' => 'Kabupaten/Kota',
            'province' => 'Provinsi',
            'postal_code' => 'Kode Pos',
            'residence_type' => 'Bertempat Tinggal di',
            'transportation' => 'Transportasi ke Sekolah',
            'distance_km' => 'Jarak Tempuh (km)',
            'travel_duration_minutes' => 'Durasi Tempuh (menit)',

            'kindergarten_origin' => 'A. Nama TK/PAUD',
            'kindergarten_address' => 'A. Alamat TK/PAUD',
            'kindergarten_npsn' => 'A. NPSN/NSM TK',
            'kindergarten_certificate_number' => 'A. No. Ijazah TK',
            'kindergarten_certificate_date' => 'A. Tanggal Ijazah TK (YYYY-MM-DD)',

            'entry_status' => 'B. Status Peserta Didik',
            'entry_year' => 'B. Tahun Masuk',
            'entry_date' => 'B. Tanggal Masuk (YYYY-MM-DD)',
            'entry_classroom' => 'B. Masuk ke Kelas',
            'report_book_serial_number' => 'B. No. Seri Rapor',

            'graduation_status' => 'C. Status Kelulusan',
            'graduation_year' => 'C. Tahun Lulus',
            'exam_number' => 'C. No. Ujian',
            'graduation_certificate_number' => 'C. No. Seri Ijazah',
            'graduation_certificate_date' => 'C. Tanggal Ijazah (YYYY-MM-DD)',
            'graduation_skl_number' => 'C. No. Seri SKL',
            'continued_to' => 'C. Melanjutkan ke Sekolah',
            'continued_to_district' => 'C. Kecamatan Sekolah Lanjutan',
            'continued_to_province' => 'C. Provinsi Sekolah Lanjutan',
            'graduation_notes' => 'C. Catatan Kelulusan',

            'transfer_out_letter_number' => 'D. No. Surat Pindah',
            'transfer_out_date' => 'D. Tanggal Pindah (YYYY-MM-DD)',
            'transfer_out_classroom' => 'D. Kelas yang Ditinggalkan',
            'transfer_out_reason' => 'D. Alasan Pindah',
            'transfer_out_nsm' => 'D. NSM Sekolah Tujuan',
            'transfer_out_npsn' => 'D. NPSN Sekolah Tujuan',
            'transfer_out_village' => 'D. Desa Sekolah Tujuan',
            'transfer_out_district' => 'D. Kecamatan Sekolah Tujuan',
            'transfer_out_province' => 'D. Provinsi Sekolah Tujuan',

            'exit_date' => 'E. Tanggal Putus Sekolah (YYYY-MM-DD)',
            'exit_classroom' => 'E. Kelas Saat Putus Sekolah',
            'exit_reason' => 'E. Alasan Putus Sekolah',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function guardianFieldLabels(): array
    {
        return [
            'name' => 'Nama',
            'gender' => 'Jenis Kelamin (L/P)',
            'nik' => 'NIK',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir (YYYY-MM-DD)',
            'religion' => 'Agama',
            'blood_type' => 'Golongan Darah',
            'last_education' => 'Pendidikan Terakhir',
            'occupation' => 'Pekerjaan',
            'monthly_income' => 'Penghasilan per Bulan',
            'phone' => 'No. WhatsApp',
            'address' => 'Alamat',
        ];
    }

    /**
     * Urutan kolom di template: kunci field => judul kolom. Kunci-kunci
     * profil/akademik/orang tua diturunkan dari daftar field
     * StudentRecordWriter supaya template selalu sinkron dengan field yang
     * benar-benar tersimpan.
     *
     * @return array<string, string>
     */
    private static function columnMap(): array
    {
        $columns = [
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'name' => 'Nama Lengkap',
            'gender' => 'Jenis Kelamin (L/P)',
            'classroom' => 'Kelas',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir (YYYY-MM-DD)',
            'address' => 'Alamat',
            'parent_name' => 'Nama Orang Tua',
            'parent_phone' => 'No. Telepon Orang Tua',
            'status' => 'Status Siswa (Aktif/Lulus/Nonaktif)',
        ];

        $labels = self::fieldLabels();

        foreach (StudentRecordWriter::PROFILE_FIELDS as $field) {
            $columns[$field] = $labels[$field] ?? Str::headline($field);
        }

        $guardianLabels = self::guardianFieldLabels();

        foreach (['father' => 'Ayah', 'mother' => 'Ibu'] as $prefix => $prefixLabel) {
            foreach (StudentRecordWriter::GUARDIAN_FIELDS as $field) {
                $columns["{$prefix}_{$field}"] = "{$prefixLabel} - ".($guardianLabels[$field] ?? Str::headline($field));
            }
        }

        foreach (StudentRecordWriter::ACADEMIC_FIELDS as $field) {
            $columns[$field] = $labels[$field] ?? Str::headline($field);
        }

        return $columns;
    }

    /**
     * Nilai contoh untuk baris kedua template. Field yang tidak relevan buat
     * contoh siswa aktif (C/D/E) sengaja dikosongkan.
     *
     * @return array<string, string>
     */
    private static function exampleValues(): array
    {
        return [
            'nisn' => '1234567890',
            'nis' => '2024001',
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'classroom' => '1-A',
            'birth_place' => 'Jakarta',
            'birth_date' => '2016-05-14',
            'address' => 'Jl. Contoh No. 1',
            'parent_name' => 'Slamet Santoso',
            'parent_phone' => '081234567890',
            'status' => 'Aktif',

            'nickname' => 'Budi',
            'nik' => '3273010101160001',
            'religion' => 'Islam',
            'family_status' => 'Anak Kandung',
            'birth_order' => '1',
            'siblings_count' => '2',
            'weight_kg' => '30',
            'height_cm' => '130',
            'blood_type' => 'O',
            'street_address' => 'Jl. Melati No. 5',
            'hamlet' => 'Dusun Suka Maju',
            'village' => 'Sukamaju',
            'district' => 'Cikarang Utara',
            'regency' => 'Bekasi',
            'province' => 'Jawa Barat',
            'postal_code' => '17530',
            'residence_type' => 'Bersama Orang Tua',
            'transportation' => 'Jalan Kaki',
            'distance_km' => '1',
            'travel_duration_minutes' => '15',

            'father_name' => 'Slamet Santoso',
            'father_gender' => 'L',
            'father_nik' => '3273010101800001',
            'father_birth_place' => 'Jakarta',
            'father_birth_date' => '1985-01-01',
            'father_religion' => 'Islam',
            'father_blood_type' => 'O',
            'father_last_education' => 'SMA/Sederajat',
            'father_occupation' => 'Wiraswasta',
            'father_monthly_income' => '3000000',
            'father_phone' => '081234567890',
            'father_address' => 'Jl. Contoh No. 1',

            'mother_name' => 'Siti Aminah',
            'mother_gender' => 'P',
            'mother_nik' => '3273010101870001',
            'mother_birth_place' => 'Jakarta',
            'mother_birth_date' => '1987-03-03',
            'mother_religion' => 'Islam',
            'mother_blood_type' => 'O',
            'mother_last_education' => 'SMA/Sederajat',
            'mother_occupation' => 'Ibu Rumah Tangga',
            'mother_monthly_income' => '-',
            'mother_phone' => '081234500000',
            'mother_address' => 'Jl. Contoh No. 1',

            'kindergarten_origin' => 'TK Melati',
            'kindergarten_address' => 'Jl. Melati No. 1',
            'kindergarten_npsn' => 'K.12345678',
            'kindergarten_certificate_number' => '001/TK/2016',
            'kindergarten_certificate_date' => '2016-06-01',

            'entry_status' => 'Peserta Didik Baru',
            'entry_year' => '2022',
            'entry_date' => '2022-07-11',
            'entry_classroom' => '1-A',
            'report_book_serial_number' => '001/RPT/2022',
        ];
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int>
     */
    private function mapColumns(array $headerRow): array
    {
        $normalized = array_map(fn ($header) => Str::lower(trim((string) $header)), $headerRow);
        $indexes = [];

        foreach (self::columnMap() as $field => $label) {
            $position = array_search(Str::lower($label), $normalized, true);
            if ($position !== false) {
                $indexes[$field] = $position;
            }
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($indexes));
        if ($missing !== []) {
            $missingLabels = collect($missing)->map(fn ($field) => self::columnMap()[$field])->implode(', ');
            throw new \InvalidArgumentException("Kolom wajib tidak ditemukan: {$missingLabels}. Gunakan template yang disediakan.");
        }

        return $indexes;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) ($value ?? '')) === '');
    }

    private function normalizeGender(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return match (Str::lower($value)) {
            'l', 'laki-laki', 'laki laki', 'laki' => 'L',
            'p', 'perempuan' => 'P',
            default => $value,
        };
    }

    /**
     * Menerjemahkan label berbahasa Indonesia (mis. "Islam", "SMA/Sederajat")
     * jadi value enum yang tersimpan di database. Kalau tidak cocok dengan
     * label maupun value manapun, teks aslinya diteruskan apa adanya supaya
     * validasi enum yang menolaknya dengan pesan yang jelas.
     *
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function enumValueFromLabel(string $enumClass, ?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        foreach ($enumClass::cases() as $case) {
            $label = method_exists($case, 'label') ? $case->label() : (string) $case->value;

            if (Str::lower($label) === Str::lower($input) || Str::lower((string) $case->value) === Str::lower($input)) {
                return $case->value;
            }
        }

        return $input;
    }

    private function findClassroom(string $name): ?Classroom
    {
        return Classroom::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
    }
}
