<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Requests\Classroom\AssignStudentsRequest;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::query()
            ->with(['homeroomTeacher', 'students' => fn ($query) => $query->orderBy('name')])
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $teachers = Teacher::where('is_active', true)->orderBy('name')->get();
        // Pilihan di "Atur Siswa": siswa aktif tanpa kelas maupun dari kelas lain (akan dipindah).
        $activeStudents = Student::query()
            ->with('classroom')
            ->where('status', StudentStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'nisn', 'classroom_id']);

        // Daftar siswa di form Tambah Kelas yang gagal disimpan, supaya tidak perlu diketik ulang.
        // Hanya dari form Tambah Kelas (Atur Siswa juga mengirim student_ids).
        $oldNewClassStudents = old('_form') === 'tambah-kelas'
            ? Student::query()->whereIn('id', (array) old('student_ids', []))->orderBy('name')->get(['id', 'name', 'nisn', 'nis'])
            : collect();

        return view('admin.data-kelas', compact('classrooms', 'teachers', 'activeStudents', 'oldNewClassStudents'));
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        $studentIds = $request->validated('student_ids', []);

        $classroom = DB::transaction(function () use ($request, $studentIds) {
            $classroom = Classroom::create($request->safe()->except('student_ids') + [
                'academic_year' => Classroom::currentAcademicYear(),
                'capacity' => Classroom::DEFAULT_CAPACITY,
            ]);

            if ($studentIds !== []) {
                Student::query()->whereIn('id', $studentIds)->whereNull('classroom_id')->update(['classroom_id' => $classroom->id]);
            }

            return $classroom;
        });

        return back()->with('success', "Kelas {$classroom->name} berhasil ditambahkan".($studentIds !== [] ? ' dengan '.count($studentIds).' siswa.' : '.'));
    }

    /**
     * Pencarian AJAX satu siswa lewat NISN/NIS untuk form Tambah Kelas.
     * 200 = boleh ditambahkan; 404 = tidak ditemukan; 422 = ditolak (sudah
     * punya kelas / tidak aktif).
     */
    public function lookupStudent(Request $request): JsonResponse
    {
        Gate::authorize('create', Classroom::class);

        $keyword = $request->string('q')->trim()->toString();
        if ($keyword === '') {
            return response()->json(['message' => 'Ketik NISN atau NIS siswa.'], 422);
        }

        // Excel/salin-tempel sering menghilangkan angka 0 di depan NISN.
        $nisn = ctype_digit($keyword) && strlen($keyword) < 10 ? str_pad($keyword, 10, '0', STR_PAD_LEFT) : $keyword;
        $student = Student::query()->with('classroom')
            ->where(fn ($query) => $query->where('nisn', $keyword)->orWhere('nisn', $nisn)->orWhere('nis', $keyword))
            ->first();

        if (! $student) {
            return response()->json(['message' => "NISN/NIS {$keyword} tidak ditemukan."], 404);
        }

        if ($issue = $student->newClassroomPlacementIssue()) {
            return response()->json(['message' => $issue], 422);
        }

        return response()->json(['student' => $student->only(['id', 'name', 'nisn', 'nis'])]);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * "Atur Siswa" — tentukan ulang seluruh anggota kelas ini.
     */
    public function assignStudents(AssignStudentsRequest $request, Classroom $classroom): RedirectResponse
    {
        $studentIds = $request->validated('student_ids', []);

        Student::where('classroom_id', $classroom->id)
            ->whereNotIn('id', $studentIds)
            ->update(['classroom_id' => null]);

        Student::whereIn('id', $studentIds)->update(['classroom_id' => $classroom->id]);

        return back()->with('success', 'Anggota kelas berhasil diperbarui.');
    }
}
