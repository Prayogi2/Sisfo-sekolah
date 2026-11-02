<?php

namespace App\Services;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EntryStatus;
use App\Enums\FamilyStatus;
use App\Enums\GraduationStatus;
use App\Enums\GuardianRelationship;
use App\Enums\Religion;
use App\Enums\ResidenceType;
use App\Enums\StudentStatus;
use App\Enums\TransportationMode;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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

    /**
     * Field yang kalau salah membuat barisnya gagal. Kesalahan di field lain
     * (isian Buku Induk) hanya mengosongkan field itu.
     */
    private const IDENTITY_FIELDS = ['nisn', 'nis', 'name', 'gender', 'classroom_id', 'status'];

    private const MAX_ROWS = 1000;

    /**
     * Isian lain yang lazim di data sekolah Indonesia (Dapodik/EMIS) untuk
     * kolom pilihan, di luar label resmi di form Buku Induk. Kunci ditulis
     * dalam bentuk optionKey().
     *
     * @var array<class-string<\BackedEnum>, array<string, string>>
     */
    private const ENUM_ALIASES = [
        EducationLevel::class => [
            'tidak sekolah' => 'tidak_sekolah',
            'tidak bersekolah' => 'tidak_sekolah',
            'belum sekolah' => 'tidak_sekolah',
            'tidak tamat sd' => 'tidak_sekolah',
            'tidak tamat sd sederajat' => 'tidak_sekolah',
            'putus sd' => 'tidak_sekolah',
            'tidak ada' => 'tidak_sekolah',
            'tidak tamat sd mi' => 'tidak_sekolah',
            'tidak tamat sekolah dasar' => 'tidak_sekolah',
            'sd' => 'sd',
            'mi' => 'sd',
            'sd mi' => 'sd',
            'sr' => 'sd',
            'sekolah dasar' => 'sd',
            'sd sederajat' => 'sd',
            'tamat sd' => 'sd',
            'madrasah ibtidaiyah' => 'sd',
            'paket a' => 'sd',
            'smp' => 'smp',
            'mts' => 'smp',
            'smp mts' => 'smp',
            'sltp' => 'smp',
            'paket b' => 'smp',
            'smp sederajat' => 'smp',
            'sekolah menengah pertama' => 'smp',
            'madrasah tsanawiyah' => 'smp',
            'sma' => 'sma',
            'smk' => 'sma',
            'ma' => 'sma',
            'mak' => 'sma',
            'smu' => 'sma',
            'stm' => 'sma',
            'smea' => 'sma',
            'slta' => 'sma',
            'sma smk' => 'sma',
            'sma ma' => 'sma',
            'sma smk ma' => 'sma',
            'paket c' => 'sma',
            'sma sederajat' => 'sma',
            'sekolah menengah atas' => 'sma',
            'sekolah menengah kejuruan' => 'sma',
            'madrasah aliyah' => 'sma',
            'd1' => 'diploma',
            'd2' => 'diploma',
            'd3' => 'diploma',
            'd4' => 'diploma',
            'diploma i' => 'diploma',
            'diploma ii' => 'diploma',
            'diploma iii' => 'diploma',
            'diploma iv' => 'diploma',
            'akademi' => 'diploma',
            'diploma 1' => 'diploma',
            'diploma 2' => 'diploma',
            'diploma 3' => 'diploma',
            'diploma 4' => 'diploma',
            'diploma satu' => 'diploma',
            'diploma dua' => 'diploma',
            'diploma tiga' => 'diploma',
            'sarjana muda' => 'diploma',
            'sarjana' => 's1',
            'strata 1' => 's1',
            'strata i' => 's1',
            'strata satu' => 's1',
            'sarjana strata' => 's1',
            'sarjana strata 1' => 's1',
            'sarjana strata i' => 's1',
            'sarjana s1' => 's1',
            's1 sarjana' => 's1',
            'sarjana strata satu' => 's1',
            's1 d4' => 's1',
            'd4 s1' => 's1',
            'magister' => 's2',
            'strata ii' => 's2',
            'strata dua' => 's2',
            'pasca sarjana' => 's2',
            'pascasarjana' => 's2',
            'sarjana strata 2' => 's2',
            'sarjana strata ii' => 's2',
            'magister s2' => 's2',
            's2 magister' => 's2',
            'strata 2' => 's2',
            'doktor' => 's3',
            'strata iii' => 's3',
            'strata tiga' => 's3',
            'doktoral' => 's3',
            'sarjana strata 3' => 's3',
            's3 doktor' => 's3',
            'strata 3' => 's3',
        ],
        TransportationMode::class => [
            'jalan' => 'jalan_kaki',
            'sepeda kayuh' => 'sepeda',
            'motor' => 'sepeda_motor',
            'sepeda motor pribadi' => 'sepeda_motor',
            'ojek' => 'sepeda_motor',
            'ojek online' => 'sepeda_motor',
            'ojol' => 'sepeda_motor',
            'diantar' => 'mobil',
            'diantar orang tua' => 'mobil',
            'antar jemput' => 'mobil',
            'antar jemput orang tua' => 'mobil',
            'mobil pribadi' => 'mobil',
            'angkot' => 'angkutan_umum',
            'angkutan kota' => 'angkutan_umum',
            'kendaraan umum' => 'angkutan_umum',
            'kendaraan umum angkot' => 'angkutan_umum',
            'bus' => 'angkutan_umum',
            'bis' => 'angkutan_umum',
            'kereta api' => 'angkutan_umum',
            'becak' => 'angkutan_umum',
            'delman' => 'angkutan_umum',
            'perahu' => 'angkutan_umum',
            'jemputan sekolah' => 'angkutan_sekolah',
            'bus sekolah' => 'angkutan_sekolah',
            'mobil bus antar jemput' => 'angkutan_sekolah',
        ],
        Religion::class => [
            'kristen protestan' => 'kristen',
            'protestan' => 'kristen',
            'katholik' => 'katolik',
            'kristen katolik' => 'katolik',
            'budha' => 'buddha',
            'khonghucu' => 'konghucu',
            'kong hu cu' => 'konghucu',
        ],
        ResidenceType::class => [
            'orang tua' => 'orang_tua',
            'bersama orangtua' => 'orang_tua',
            'wali' => 'wali',
            'asrama' => 'asrama_pondok',
            'pondok' => 'asrama_pondok',
            'pondok pesantren' => 'asrama_pondok',
            'pesantren' => 'asrama_pondok',
            'kos' => 'kos_kontrak',
            'kost' => 'kos_kontrak',
            'kontrak' => 'kos_kontrak',
        ],
    ];

    /**
     * Singkatan bulan (Indonesia & Inggris) -> nomor bulan.
     *
     * @var array<string, int>
     */
    private const MONTH_NAMES = [
        'jan' => 1, 'feb' => 2, 'peb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5,
        'jun' => 6, 'jul' => 7, 'agu' => 8, 'agt' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10,
        'oct' => 10, 'nov' => 11, 'nop' => 11, 'des' => 12, 'dec' => 12,
    ];

    /**
     * Judul kolom lain yang lazim dipakai (mis. file Dapodik/EMIS atau
     * template yang judulnya diubah). Dipakai hanya bila judul persis dari
     * template tidak ditemukan, dan diambil dari kolom yang belum terpakai —
     * jadi dua kolom "Nama Ayah Kandung" terbagi ke Nama Orang Tua lalu
     * Ayah - Nama, dan "Alamat" kedua jadi alamat ibu.
     *
     * @var array<string, list<string>>
     */
    private const COLUMN_ALIASES = [
        'nisn' => ['nomor induk siswa nasional'],
        'nis' => ['nis lokal', 'nipd', 'nomor induk siswa'],
        'name' => ['nama', 'nama siswa', 'nama peserta didik'],
        'gender' => ['jenis kelamin', 'jk', 'l/p', 'kelamin', 'gender'],
        'classroom' => ['tingkat - rombel', 'tingkat-rombel', 'rombel', 'rombongan belajar', 'kelas/rombel'],
        'parent_name' => ['nama ayah kandung', 'nama ayah', 'nama orang tua/wali', 'nama wali'],
        'father_name' => ['nama ayah kandung', 'nama ayah'],
        'mother_name' => ['nama ibu kandung', 'nama ibu'],
        'father_address' => ['alamat ayah'],
        'mother_address' => ['alamat ibu', 'alamat'],
    ];

    /**
     * Kolom yang boleh berbagi sel dengan kolom lain kalau sumbernya cuma
     * satu, mis. satu kolom "Nama Ayah Kandung" dipakai untuk Nama Orang Tua
     * sekaligus Ayah - Nama.
     */
    private const SHAREABLE_ALIAS_COLUMNS = ['parent_name', 'father_name', 'mother_name'];

    /**
     * Potongan nama unique index di database -> sebutan kolomnya untuk admin.
     *
     * @var array<string, string>
     */
    private const DUPLICATE_KEY_LABELS = [
        'students_nisn' => 'NISN',
        'students_nis' => 'NIS',
        'guardians_nik' => 'NIK orang tua',
        'student_profiles_nik' => 'NIK siswa',
        'users_username' => 'Username akun login',
        'users_email' => 'Email akun login',
    ];

    /**
     * @var Collection<int, Classroom>|null
     */
    private ?Collection $classrooms = null;

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
        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('File tidak bisa dibaca. Gunakan file XLSX/XLS/CSV dari tombol Download Template.');
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headerRow = array_shift($rows) ?? [];

        // Nilai mentah (tanpa format tampilan Excel) dipakai untuk kolom
        // tanggal: sel bertipe tanggal tersimpan sebagai nomor seri, jadi
        // tidak bergantung pada format tampilan/locale komputer pembuatnya.
        $rawRows = $sheet->toArray(null, true, false, false);
        array_shift($rawRows);

        $columnIndexes = $this->mapColumns($headerRow);
        $dateFields = collect($this->rules(null))
            ->filter(fn (array $rules) => in_array('date', $rules, true))
            ->keys()
            ->all();

        if (count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('File berisi lebih dari '.self::MAX_ROWS.' baris data. Pecah menjadi beberapa file terlebih dahulu.');
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $warnings = [];

        foreach ($rows as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $sheetRowNumber = $index + 2;
            $value = fn (string $field) => isset($columnIndexes[$field]) ? self::cleanCell($row[$columnIndexes[$field]] ?? null) : null;
            $rawValue = fn (string $field) => isset($columnIndexes[$field]) ? ($rawRows[$index][$columnIndexes[$field]] ?? null) : null;

            $classroomName = $value('classroom');
            $classroom = $classroomName ? $this->findClassroom($classroomName) : null;

            $nisn = $value('nisn');
            $existingStudent = $nisn ? Student::query()->where('nisn', $nisn)->first() : null;

            $payload = $this->buildPayload($value, $classroom);

            foreach ($dateFields as $field) {
                $payload[$field] = $this->normalizeDate($payload[$field] ?? null, $rawValue($field));
            }

            $validator = Validator::make($payload, $this->rules($existingStudent), $this->messages(), self::attributeNames());

            // Panggil fails() dulu untuk menjalankan validasi & mengisi
            // message bag-nya. Menambah error manual sebelum fails()
            // percuma: fails() memanggil passes(), yang selalu membuat
            // message bag baru dan membuang tambahan manual itu.
            $rulesFailed = $validator->fails();
            $fieldErrors = $rulesFailed ? $validator->errors()->messages() : [];
            $identityErrors = Arr::only($fieldErrors, self::IDENTITY_FIELDS);

            if ($classroomName && $classroom === null) {
                $identityErrors['classroom'] = ["Kelas \"{$classroomName}\" tidak ditemukan. Buat dulu kelasnya di menu Pembagian Kelas, atau kosongkan kolom kelas."];
            }

            if ($identityErrors !== []) {
                $errors[] = "Baris {$sheetRowNumber}: ".implode(' ', Arr::flatten($identityErrors));

                continue;
            }

            // Isian Buku Induk yang salah tidak menggagalkan siswanya: kolom
            // itu dikosongkan, siswanya tetap masuk, dan admin diberi tahu
            // supaya bisa melengkapinya nanti dari halaman Buku Induk.
            $skippedFields = Arr::except($fieldErrors, self::IDENTITY_FIELDS) + $this->guardianNikConflicts($payload);

            if ($skippedFields !== []) {
                $payload = array_replace($payload, array_fill_keys(array_keys($skippedFields), null));
                $validator = Validator::make($payload, $this->rules($existingStudent), $this->messages(), self::attributeNames());

                if ($validator->fails()) {
                    $errors[] = "Baris {$sheetRowNumber}: ".implode(' ', $validator->errors()->all());

                    continue;
                }
            }

            try {
                if ($existingStudent) {
                    $this->updateExisting($existingStudent, $validator->validated());
                    $updated++;
                } else {
                    $this->enroller->enroll($validator->validated());
                    $created++;
                }
            } catch (ValidationException $e) {
                $errors[] = "Baris {$sheetRowNumber}: ".implode(' ', $e->validator->errors()->all());

                continue;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$sheetRowNumber}: ".$this->saveFailureMessage($e);

                continue;
            }

            if ($skippedFields !== []) {
                $warnings[] = "Baris {$sheetRowNumber} ({$payload['name']}): tersimpan, tetapi isian ini dikosongkan karena tidak valid — ".implode(' ', Arr::flatten($skippedFields));
            }
        }

        return new StudentImportResult($created, $errors, $updated, $warnings);
    }

    /**
     * Isi sel sebagai teks bersih. Tanda kutip satu di depan (cara Excel
     * menyimpan angka sebagai teks, mis. NIK '1207...) ikut dibuang.
     */
    private static function cleanCell(mixed $cell): string
    {
        $text = trim((string) ($cell ?? ''));

        return str_starts_with($text, "'") ? trim(substr($text, 1)) : $text;
    }

    /**
     * NIK orang tua dipakai untuk menautkan kakak-beradik ke orang tua yang
     * sama — satu ibu bisa punya beberapa anak di sekolah ini, jadi NIK yang
     * sudah tercatat tetap dipakai meski nama di baris ini ditulis sedikit
     * berbeda. NIK baru ditolak bila sudah tercatat untuk peran lain (mis.
     * NIK ibu dipakai sebagai NIK ayah).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, list<string>>
     */
    private function guardianNikConflicts(array $payload): array
    {
        $conflicts = [];

        foreach (['father' => GuardianRelationship::Father, 'mother' => GuardianRelationship::Mother] as $prefix => $relationship) {
            $nik = $payload["{$prefix}_nik"] ?? null;

            if (! $nik) {
                continue;
            }

            $owner = Guardian::query()->where('nik', $nik)->first();

            if ($owner === null || $owner->relationship === $relationship) {
                continue;
            }

            $conflicts["{$prefix}_nik"] = ["{$relationship->label()} - NIK \"{$nik}\" sudah dipakai oleh {$owner->name} sebagai {$owner->relationship->label()}."];
        }

        return $conflicts;
    }

    /**
     * Pesan siap-baca untuk kegagalan penyimpanan yang tidak tertangkap
     * validasi. Pesan asli (beserta query-nya) hanya masuk log — admin cukup
     * tahu data mana yang bentrok, bukan SQL-nya.
     */
    private function saveFailureMessage(\Throwable $e): string
    {
        Log::warning('Import siswa: baris gagal disimpan.', ['exception' => $e]);

        if ($e instanceof QueryException) {
            return $this->duplicateEntryMessage($e) ?? 'gagal disimpan karena data tidak diterima database. Periksa kembali isian baris ini.';
        }

        return 'gagal disimpan. Periksa kembali isian baris ini.';
    }

    /**
     * Terjemahkan pelanggaran unique index jadi nama kolom yang dikenal admin.
     * Formatnya beda per driver: MySQL menyebut nama index dan nilainya,
     * SQLite hanya menyebut tabel.kolom.
     */
    private function duplicateEntryMessage(QueryException $e): ?string
    {
        $message = $e->getMessage();
        $value = null;

        if (preg_match("/Duplicate entry '(.*)' for key '([^']+)'/", $message, $matches)) {
            [, $value, $key] = $matches;
        } elseif (preg_match('/UNIQUE constraint failed: ([A-Za-z0-9_.]+)/', $message, $matches)) {
            $key = str_replace('.', '_', $matches[1]);
        } else {
            return null;
        }

        $label = collect(self::DUPLICATE_KEY_LABELS)
            ->first(fn (string $label, string $needle) => str_contains($key, $needle)) ?? 'Data';

        return $value === null
            ? "{$label} sudah terpakai oleh data lain."
            : "{$label} \"{$value}\" sudah terpakai oleh data lain.";
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
        $messages = [
            'gender.in' => 'Jenis kelamin harus diisi L atau P.',
            'nisn.digits' => 'NISN harus 10 digit angka (format kolom NISN sebagai Teks di Excel agar angka 0 di depan tidak hilang).',
        ];

        foreach (self::enumFields() as $field => $enumClass) {
            $options = collect($enumClass::cases())
                ->map(fn (\BackedEnum $case) => method_exists($case, 'label') ? $case->label() : (string) $case->value)
                ->implode(', ');

            $messages["{$field}.enum"] = ':attribute ":input" tidak dikenali. Isi dengan salah satu: '.$options.'.';
        }

        $messages['birth_date.date'] = 'Tanggal Lahir ":input" bukan tanggal yang valid. Isi dengan format DD/MM/YYYY, mis. 14/05/2016.';

        // Pesan umum per aturan untuk kolom yang tidak punya pesan khusus.
        return $messages + $this->recordWriter->messages() + [
            'required' => ':attribute wajib diisi.',
            'date' => ':attribute ":input" bukan tanggal yang valid. Isi dengan format DD/MM/YYYY, mis. 13/07/2022.',
            'integer' => ':attribute harus berupa angka bulat.',
            'digits' => ':attribute harus :digits digit angka.',
            'in' => ':attribute harus diisi L atau P.',
            'unique' => ':attribute ":input" sudah dipakai oleh siswa lain.',
            'regex' => 'Format :attribute tidak valid.',
            'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.'],
            'max' => ['numeric' => ':attribute maksimal :max.', 'string' => ':attribute maksimal :max karakter.'],
        ];
    }

    /**
     * Nama kolom untuk pesan error = judul kolom di template (tanpa
     * keterangan format), supaya admin langsung tahu kolom mana yang salah.
     *
     * @return array<string, string>
     */
    private static function attributeNames(): array
    {
        return collect(self::columnMap())
            ->map(fn (string $label) => trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $label) ?? $label))
            ->all();
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

        // NIK "0" / "-" / "0000..." = tidak diisi. Kalau disimpan, semua
        // orang tua ber-NIK "0" akan dianggap orang yang sama.
        foreach (['nik', 'father_nik', 'mother_nik'] as $field) {
            if (preg_match('/^[0\s.\-]*$/', (string) ($payload[$field] ?? ''))) {
                $payload[$field] = null;
            }
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
            'kindergarten_certificate_date' => 'A. Tanggal Ijazah TK (DD/MM/YYYY)',

            'entry_status' => 'B. Status Peserta Didik',
            'entry_year' => 'B. Tahun Masuk',
            'entry_date' => 'B. Tanggal Masuk (DD/MM/YYYY)',
            'entry_classroom' => 'B. Masuk ke Kelas',
            'report_book_serial_number' => 'B. No. Seri Rapor',

            'graduation_status' => 'C. Status Kelulusan',
            'graduation_year' => 'C. Tahun Lulus',
            'exam_number' => 'C. No. Ujian',
            'graduation_certificate_number' => 'C. No. Seri Ijazah',
            'graduation_certificate_date' => 'C. Tanggal Ijazah (DD/MM/YYYY)',
            'graduation_skl_number' => 'C. No. Seri SKL',
            'continued_to' => 'C. Melanjutkan ke Sekolah',
            'continued_to_district' => 'C. Kecamatan Sekolah Lanjutan',
            'continued_to_province' => 'C. Provinsi Sekolah Lanjutan',
            'graduation_notes' => 'C. Catatan Kelulusan',

            'transfer_out_letter_number' => 'D. No. Surat Pindah',
            'transfer_out_date' => 'D. Tanggal Pindah (DD/MM/YYYY)',
            'transfer_out_classroom' => 'D. Kelas yang Ditinggalkan',
            'transfer_out_reason' => 'D. Alasan Pindah',
            'transfer_out_nsm' => 'D. NSM Sekolah Tujuan',
            'transfer_out_npsn' => 'D. NPSN Sekolah Tujuan',
            'transfer_out_village' => 'D. Desa Sekolah Tujuan',
            'transfer_out_district' => 'D. Kecamatan Sekolah Tujuan',
            'transfer_out_province' => 'D. Provinsi Sekolah Tujuan',

            'exit_date' => 'E. Tanggal Putus Sekolah (DD/MM/YYYY)',
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
            'birth_date' => 'Tanggal Lahir (DD/MM/YYYY)',
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
            'birth_date' => 'Tanggal Lahir (DD/MM/YYYY)',
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
            'birth_date' => '14/05/2016',
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
            'father_birth_date' => '01/01/1985',
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
            'mother_birth_date' => '03/03/1987',
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
            'kindergarten_certificate_date' => '01/06/2016',

            'entry_status' => 'Peserta Didik Baru',
            'entry_year' => '2022',
            'entry_date' => '11/07/2022',
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
        $normalized = array_map(fn ($header) => self::normalizeHeader((string) $header), $headerRow);
        $indexes = [];

        foreach (self::columnMap() as $field => $label) {
            $position = array_search(self::normalizeHeader($label), $normalized, true);
            if ($position !== false) {
                $indexes[$field] = $position;
            }
        }

        foreach (self::COLUMN_ALIASES as $field => $aliases) {
            if (isset($indexes[$field])) {
                continue;
            }

            $matches = collect($aliases)
                ->flatMap(fn (string $alias) => array_keys($normalized, self::normalizeHeader($alias), true))
                ->all();
            $unused = array_values(array_diff($matches, $indexes));

            if ($unused !== []) {
                $indexes[$field] = $unused[0];
            } elseif ($matches !== [] && in_array($field, self::SHAREABLE_ALIAS_COLUMNS, true)) {
                $indexes[$field] = $matches[0];
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
     * Seragamkan judul kolom sebelum dicocokkan: huruf kecil, spasi tak
     * terlihat (non-breaking space, tab, enter) jadi spasi biasa, spasi
     * ganda diringkas, dan spasi di sekitar "/", "(" dan ")" dibuang —
     * supaya "Jenis  Kelamin ( L / P )" tetap dikenali.
     */
    private static function normalizeHeader(string $header): string
    {
        $header = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', ' ', $header) ?? $header;
        $header = preg_replace('/\s*([\/()])\s*/u', '$1', $header) ?? $header;
        $header = str_replace(['(dd/mm/yyyy)', '(yyyy-mm-dd)'], '', Str::lower($header));

        return trim($header);
    }

    /**
     * Ubah isian tanggal jadi YYYY-MM-DD. Isian teks dibaca dengan urutan
     * Indonesia (hari/bulan/tahun), bukan urutan Amerika yang dipakai
     * strtotime() — tanpa ini "05/07/2022" tersimpan sebagai 7 Mei dan
     * "13/07/2022" ditolak. Isian yang tidak dikenali diteruskan apa adanya
     * supaya ditolak validasi dengan pesan yang jelas.
     */
    private function normalizeDate(?string $formatted, mixed $raw): ?string
    {
        if (is_int($raw) || is_float($raw)) {
            return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d');
        }

        $input = trim((string) $formatted);

        if ($input === '' || $input === '-') {
            return null;
        }

        if (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $input, $matches)) {
            [, $year, $month, $day] = $matches;
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $input, $matches)) {
            [, $day, $month, $year] = $matches;
        } elseif (preg_match('/^(\d{1,2})[\s\-]+([[:alpha:]]+)\.?[\s\-,]+(\d{4})$/u', $input, $matches)) {
            [, $day, $monthName, $year] = $matches;
            $month = self::MONTH_NAMES[Str::lower(Str::substr($monthName, 0, 3))] ?? null;
        } else {
            return $input;
        }

        if (strlen($year) === 2) {
            $year = ((int) $year > (int) date('y') ? '19' : '20').$year;
        }

        if ($month === null || ! checkdate((int) $month, (int) $day, (int) $year)) {
            return $input;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
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
            'l', 'lk', 'laki-laki', 'laki laki', 'laki', 'pria' => 'L',
            'p', 'pr', 'perempuan', 'wanita' => 'P',
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

        if ($input === '' || $input === '-') {
            return null;
        }

        $key = self::optionKey($input);
        $candidates = self::ENUM_ALIASES[$enumClass] ?? [];

        foreach ($enumClass::cases() as $case) {
            $label = method_exists($case, 'label') ? $case->label() : (string) $case->value;
            $candidates[self::optionKey($label)] = $case->value;
            $candidates[self::optionKey((string) $case->value)] = $case->value;
        }

        if (isset($candidates[$key])) {
            return $candidates[$key];
        }

        // Salah ketik kecil (mis. "Sepeda mototr") tetap dikenali, asal
        // hanya mirip dengan satu pilihan.
        if (mb_strlen($key) >= 5) {
            $closeValues = collect($candidates)
                ->filter(fn (string $value, string $candidate) => levenshtein($candidate, $key) <= 2)
                ->unique();

            if ($closeValues->count() === 1) {
                return $closeValues->first();
            }
        }

        return $input;
    }

    /**
     * Kunci pencocokan isian pilihan: huruf kecil, tanda baca & spasi
     * diseragamkan, supaya "SD / Sederajat" = "sd/sederajat".
     */
    private static function optionKey(string $value): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', Str::lower($value)) ?? $value);
    }

    private function findClassroom(string $name): ?Classroom
    {
        $this->classrooms ??= Classroom::query()->get(['id', 'name']);
        $key = self::classroomKey($name);

        return $this->classrooms->first(fn (Classroom $classroom) => self::classroomKey($classroom->name) === $key);
    }

    /**
     * Kunci pencocokan nama kelas: tanpa awalan "Kelas", spasi & tanda baca,
     * sehingga "1-A", "1 A", dan "Kelas 1A" dianggap sama. Format EMIS
     * "Kelas 1 - 1 A" (Tingkat - Rombel) diambil rombelnya saja.
     */
    private static function classroomKey(string $name): string
    {
        $name = preg_replace('/^\s*kelas\s+/iu', '', $name) ?? $name;

        if (preg_match('/^(\w+)\s*-\s*(.+)$/u', $name, $matches) && str_starts_with(Str::lower($matches[2]), Str::lower($matches[1]))) {
            $name = $matches[2];
        }

        return preg_replace('/[^\p{L}\p{N}]+/u', '', Str::lower($name)) ?? $name;
    }
}
