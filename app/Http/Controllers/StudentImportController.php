<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\ImportStudentsRequest;
use App\Models\Student;
use App\Services\ReportExportService;
use App\Services\StudentImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentImportController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('admin.data-siswa-import');
    }

    public function template(ReportExportService $exporter)
    {
        Gate::authorize('create', Student::class);

        return $exporter->xlsx(
            'template-data-siswa.xlsx',
            StudentImporter::templateHeaders(),
            [StudentImporter::templateExampleRow()],
        );
    }

    public function store(ImportStudentsRequest $request, StudentImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.data-siswa')->with('import_result', $result);
    }
}
