<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classroom\ImportClassroomStudentsRequest;
use App\Models\Classroom;
use App\Services\ClassroomStudentImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Import siswa (yang sudah terdaftar) ke satu kelas lewat Excel.
 */
class ClassroomImportController extends Controller
{
    public function template(Classroom $classroom, ClassroomStudentImporter $importer)
    {
        Gate::authorize('manageStudents', $classroom);

        return $importer->template($classroom);
    }

    public function store(ImportClassroomStudentsRequest $request, Classroom $classroom, ClassroomStudentImporter $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file'), $classroom);

        return redirect()->route('admin.pembagian-kelas')->with('classroom_import_result', $result);
    }
}
