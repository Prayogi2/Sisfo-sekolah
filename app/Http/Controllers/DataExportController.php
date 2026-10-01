<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Unduh data master (guru, siswa, inventaris kelas) sebagai Excel atau PDF.
 */
class DataExportController extends Controller
{
    public function __construct(private ReportExportService $exporter) {}

    public function teachers(string $format)
    {
        Gate::authorize('viewAny', Teacher::class);
        $this->assertFormat($format);

        $rows = Teacher::query()
            ->with(['homeroomClassrooms', 'teachingAssignments.subject', 'teachingAssignments.classroom', 'user.roles'])
            ->orderBy('name')
            ->get()
            ->map(fn (Teacher $teacher) => [
                (string) $teacher->nip,
                $teacher->name,
                $teacher->gender?->label() ?? '-',
                $teacher->assignmentSummary() ?: '-',
                $teacher->homeroomClassrooms->pluck('name')->join(', ') ?: '-',
                $teacher->phone ?: '-',
                $teacher->email ?: '-',
                $teacher->user?->hasRole('admin') ? 'Ya' : 'Tidak',
            ]);

        return $this->exporter->table($format, 'data-guru-'.now()->format('Ymd'), 'Data Guru & Wali Kelas', ['NIP/NUPTK', 'Nama', 'L/P', 'Mapel & Kelas', 'Wali Kelas', 'No. WhatsApp', 'Email', 'Akses Admin'], $rows, orientation: 'landscape');
    }

    /**
     * Mengikuti filter halaman Data Siswa (?search=, ?classroom_id=).
     */
    public function students(Request $request, string $format)
    {
        Gate::authorize('viewAny', Student::class);
        $this->assertFormat($format);

        $classroom = $request->integer('classroom_id') ? Classroom::find($request->integer('classroom_id')) : null;
        $rows = Student::query()
            ->with('classroom')
            ->when($request->query('search'), fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%")))
            ->when($classroom, fn ($query) => $query->where('classroom_id', $classroom->id))
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student) => [
                (string) $student->nisn,
                (string) $student->nis,
                $student->name,
                $student->gender?->value ?? '-',
                $student->classroom?->name ?? '-',
                collect([$student->birth_place, $student->birth_date?->format('d-m-Y')])->filter()->join(', ') ?: '-',
                $student->parent_name ?: '-',
                $student->parent_phone ?: '-',
                $student->status?->label() ?? '-',
            ]);

        return $this->exporter->table($format, 'data-siswa-'.now()->format('Ymd'), 'Data Siswa', ['NISN', 'NIS', 'Nama', 'L/P', 'Kelas', 'Tempat, Tgl Lahir', 'Orang Tua', 'No. Orang Tua', 'Status'], $rows, $classroom ? 'Kelas '.$classroom->name : null, 'landscape');
    }

    public function inventory(Classroom $classroom, string $format)
    {
        Gate::authorize('viewInventory', $classroom);
        $this->assertFormat($format);

        $rows = $classroom->inventoryItems()
            ->get()
            ->sortBy([fn (InventoryItem $item) => array_search($item->category, InventoryCategory::cases(), true), 'sort_order'])
            ->map(fn (InventoryItem $item) => [
                $item->category->label(),
                $item->name,
                $item->totalQuantity(),
                $item->good_quantity,
                $item->damaged_quantity,
                $item->notes ?: '-',
                $item->last_reported_at?->format('d-m-Y') ?? 'Belum',
            ])
            ->values();

        return $this->exporter->table($format, 'inventaris-kelas-'.str($classroom->name)->slug(), 'Inventaris Kelas '.$classroom->name, ['Kategori', 'Nama Barang', 'Jumlah', 'Baik', 'Rusak', 'Keterangan', 'Terakhir Dilaporkan'], $rows, 'Wali kelas: '.($classroom->homeroomTeacher?->name ?? '-'));
    }

    private function assertFormat(string $format): void
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
    }
}
