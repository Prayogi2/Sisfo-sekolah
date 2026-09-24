<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportBook\ImportReportBookRequest;
use App\Http\Requests\ReportBook\UpdateReportBookRequest;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ReportBook;
use App\Services\ReportBookSpreadsheet;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tabel "Nilai Laporan Hasil Belajar Peserta Didik" di buku induk (khusus
 * admin): isi manual, export/import Excel, dan cetak PDF per siswa.
 */
class ReportBookController extends Controller
{
    public function edit(Student $student, ReportBook $reportBook): View
    {
        Gate::authorize('update', $student);

        $student->load('academicRecord');

        return view('admin.buku-induk-nilai-edit', [
            'student' => $student,
            'summary' => $reportBook->summary($student),
            'subjectSuggestions' => Subject::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateReportBookRequest $request, Student $student, ReportBook $reportBook): RedirectResponse
    {
        $reportBook->replace($student, $request->validated('subjects', []), $request->validated('years', []));

        return redirect()->route('admin.buku-induk', ['student' => $student->id, 'tab' => 'akademik'])
            ->with('success', 'Nilai buku induk siswa berhasil disimpan.');
    }

    public function exportXlsx(Student $student, ReportBookSpreadsheet $spreadsheet)
    {
        Gate::authorize('view', $student);

        return $spreadsheet->export($student);
    }

    /**
     * Import menggantikan seluruh isi tabel nilai buku induk siswa ini
     * dengan isi file, setelah seluruh file lolos validasi.
     */
    public function importXlsx(ImportReportBookRequest $request, Student $student, ReportBookSpreadsheet $spreadsheet, ReportBook $reportBook): RedirectResponse
    {
        $data = $spreadsheet->parse($request->file('file'), $student);
        $reportBook->replace($student, $data['subjects'], $data['years']);

        return redirect()->route('admin.buku-induk', ['student' => $student->id, 'tab' => 'akademik'])
            ->with('success', 'Import berhasil: '.count($data['subjects']).' mata pelajaran tersimpan di nilai buku induk siswa ini.');
    }

    public function pdf(Student $student, ReportBook $reportBook, ReportExportService $exporter)
    {
        Gate::authorize('view', $student);

        $student->load('academicRecord');
        $slug = str($student->name)->slug();

        return $exporter->pdf('admin.exports.nilai-buku-induk', [
            'student' => $student,
            'summary' => $reportBook->summary($student),
        ], "nilai-buku-induk-{$student->nisn}-{$slug}.pdf");
    }
}
