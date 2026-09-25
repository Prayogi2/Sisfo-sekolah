<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentEnroller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Halaman "Peserta Presensi": tampilan & form ringkas khusus data yang
 * dipakai presensi (nama, kelas, wali kelas, tahun pelajaran, QR, NISN/NIS,
 * alamat, status). Tidak punya tabel sendiri — datanya tetap tabel
 * `students` & `classrooms`, sama dengan Data Siswa & Buku Induk, supaya
 * data siswa tidak tersimpan ganda.
 */
class AttendanceParticipantController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $search = $request->string('search')->trim()->toString();

        $students = Student::query()
            ->with('classroom.homeroomTeacher')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            }))
            ->when($request->integer('classroom_id'), fn ($query, int $classroomId) => $query->where('classroom_id', $classroomId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.peserta-presensi', [
            'students' => $students,
            'classrooms' => Classroom::query()->with('homeroomTeacher')->orderBy('grade_level')->orderBy('name')->get(),
            'statuses' => StudentStatus::cases(),
            'search' => $search,
        ]);
    }

    /**
     * Tambah peserta presensi = tambah siswa lewat alur yang sama dengan
     * Data Siswa: QR & akun login siswa otomatis dibuat. Data Buku Induk
     * lainnya bisa dilengkapi menyusul.
     */
    public function store(StoreStudentRequest $request, StudentEnroller $enroller): RedirectResponse
    {
        $student = $enroller->enroll($request->validated());

        return redirect()->route('admin.peserta-presensi', ['search' => $student->nisn])
            ->with('success', "{$student->name} ditambahkan sebagai peserta presensi. Kartu QR-nya sudah bisa dicetak.");
    }
}
