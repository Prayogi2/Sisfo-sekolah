<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['user_id', 'nip', 'name', 'gender', 'phone', 'address', 'email', 'is_active'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * The user account linked to this teacher, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The classrooms this teacher is the homeroom teacher (wali kelas) for.
     */
    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    /**
     * Baris penugasan mapel + kelas milik guru ini. Sumber kebenaran untuk
     * subjects()/classrooms() di bawah, dan untuk membatasi akses guru per
     * kelas (kuis, nilai, dsb) sesuai kelas yang benar-benar ia ajarkan.
     */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /**
     * Mapel yang diajarkan guru ini (tanpa duplikat), tanpa peduli di kelas
     * mana. Dipakai untuk menyaring bank soal & rekap nilai per mapel.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teaching_assignments')->distinct();
    }

    /**
     * Kelas yang diajarkan guru ini (tanpa duplikat), gabungan dari semua
     * mapelnya. Untuk memastikan guru hanya mengakses kelas+mapel yang
     * benar-benar ditugaskan, cek teachingAssignments(), bukan relasi ini.
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'teaching_assignments')->distinct();
    }

    /**
     * Apakah guru ini ditugaskan mengajar mapel tersebut di kelas tersebut.
     */
    public function teaches(int $subjectId, int $classroomId): bool
    {
        return $this->teachingAssignments()
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    /**
     * Kelas-kelas yang diajarkan guru ini khusus untuk satu mapel tertentu.
     *
     * @return Collection<int, Classroom>
     */
    public function classroomsForSubject(int $subjectId): Collection
    {
        return $this->teachingAssignments()
            ->where('subject_id', $subjectId)
            ->with('classroom')
            ->get()
            ->pluck('classroom')
            ->sortBy('name')
            ->values();
    }

    /**
     * Ringkasan "Mapel (Kelas A, Kelas B)" untuk ditampilkan di tabel guru.
     */
    public function assignmentSummary(): string
    {
        return $this->teachingAssignments
            ->loadMissing(['subject', 'classroom'])
            ->groupBy(fn (TeachingAssignment $assignment) => $assignment->subject->name)
            ->map(fn (Collection $rows, string $subjectName) => $subjectName.' ('.$rows->pluck('classroom.name')->sort()->implode(', ').')')
            ->sort()
            ->implode(', ');
    }
}
