<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'classroom_id',
    'nisn',
    'nis',
    'name',
    'gender',
    'birth_place',
    'birth_date',
    'address',
    'parent_name',
    'parent_phone',
    'status',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'status' => StudentStatus::class,
        ];
    }

    /**
     * The user account linked to this student, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The classroom this student is enrolled in.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * The guardians (parents/wali) linked to this student.
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student');
    }

    /**
     * Data identitas lengkap buku induk (tab "Data Peserta Didik").
     */
    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Riwayat pendidikan & kelulusan buku induk.
     */
    public function academicRecord(): HasOne
    {
        return $this->hasOne(StudentAcademicRecord::class);
    }

    /**
     * Riwayat absensi harian siswa.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(StudentViolation::class);
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(StudentProgressNote::class);
    }

    /**
     * Baris mata pelajaran tabel nilai buku induk, sesuai urutan di form.
     */
    public function reportBookGrades(): HasMany
    {
        return $this->hasMany(ReportBookGrade::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Tahun Ajaran & "Naik ke Kelas" tiap tingkat kelas di buku induk.
     */
    public function reportBookYears(): HasMany
    {
        return $this->hasMany(ReportBookYear::class);
    }

    /**
     * Alasan siswa ini TIDAK boleh dimasukkan ke kelas baru (form Tambah
     * Kelas), atau null bila boleh. Siswa yang sudah punya kelas ditolak,
     * bukan dipindah otomatis — sama dengan aturan Import Excel per kelas.
     */
    public function newClassroomPlacementIssue(): ?string
    {
        if ($this->status !== StudentStatus::Active) {
            return "{$this->name} berstatus {$this->status->label()}, bukan siswa aktif.";
        }

        if ($this->classroom_id !== null) {
            $this->loadMissing('classroom');

            return "{$this->name} sudah terdaftar di kelas {$this->classroom?->name}.";
        }

        return null;
    }

    /**
     * Nomor WhatsApp orang tua untuk notifikasi: No. HP orang tua di data
     * siswa, atau bila kosong No. WhatsApp ayah → ibu → wali di Buku Induk.
     */
    public function parentWhatsAppNumber(): ?string
    {
        if (filled($this->parent_phone)) {
            return $this->parent_phone;
        }

        return $this->guardians
            ->sortBy(fn (Guardian $guardian) => array_search($guardian->relationship, [GuardianRelationship::Father, GuardianRelationship::Mother, GuardianRelationship::Guardian], true))
            ->first(fn (Guardian $guardian) => filled($guardian->phone))
            ?->phone;
    }

    /**
     * Notifikasi dari admin yang ditujukan ke siswa ini.
     */
    public function announcementRecipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }
}
