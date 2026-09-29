<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\StoreInventoryReportRequest;
use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\InventoryReport;
use App\Models\InventoryReportItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inventaris kelas. Admin mengelola daftar barang semua kelas (tambah &
 * hapus); wali kelas menambah barang dan mengirim laporan kondisi kelasnya.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $isAdminPage = $request->routeIs('admin.*');
        $user = $request->user();

        $classrooms = Classroom::query()
            ->with('homeroomTeacher')
            ->unless($isAdminPage, fn ($query) => $query->whereHas('homeroomTeacher', fn ($query) => $query->where('user_id', $user->id)))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $classroom = $classrooms->firstWhere('id', $request->integer('classroom')) ?? $classrooms->first();

        if ($classroom) {
            Gate::authorize('viewInventory', $classroom);
        }

        $itemsByCategory = $classroom
            ? $this->groupByCategory($classroom->inventoryItems()->orderBy('sort_order')->orderBy('id')->get())
            : collect();

        $reports = $classroom
            ? $classroom->inventoryReports()
                ->with('reporter')
                ->withSum('items as good_total', 'good_quantity')
                ->withSum('items as damaged_total', 'damaged_quantity')
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        return view($isAdminPage ? 'admin.inventaris' : 'guru.inventaris', [
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'itemsByCategory' => $itemsByCategory,
            'reports' => $reports,
            'categories' => InventoryCategory::cases(),
        ]);
    }

    public function storeItem(StoreInventoryItemRequest $request, Classroom $classroom): RedirectResponse
    {
        $sortOrder = (int) $classroom->inventoryItems()->max('sort_order');
        $items = $request->validated('items');

        DB::transaction(function () use ($classroom, $items, $request, &$sortOrder) {
            foreach ($items as $item) {
                $classroom->inventoryItems()->create([
                    'category' => $item['category'],
                    'name' => $item['name'],
                    // Barang yang baru dicatat dianggap dalam kondisi baik.
                    'good_quantity' => (int) $item['quantity'],
                    'sort_order' => ++$sortOrder,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('success', count($items).' barang inventaris berhasil ditambahkan.');
    }

    /**
     * Tambahkan daftar barang standar sekolah yang belum ada di kelas ini.
     */
    public function storeDefaultItems(Request $request, Classroom $classroom): RedirectResponse
    {
        Gate::authorize('addInventoryItem', $classroom);

        $existing = $classroom->inventoryItems()->get(['category', 'name'])
            ->map(fn (InventoryItem $item) => $item->category->value.'|'.mb_strtolower($item->name));
        $sortOrder = (int) $classroom->inventoryItems()->max('sort_order');
        $added = 0;

        foreach (InventoryCategory::cases() as $category) {
            foreach ($category->defaultItems() as $name) {
                if ($existing->contains($category->value.'|'.mb_strtolower($name))) {
                    continue;
                }

                $classroom->inventoryItems()->create([
                    'category' => $category,
                    'name' => $name,
                    'sort_order' => ++$sortOrder,
                    'created_by' => $request->user()->id,
                ]);
                $added++;
            }
        }

        return back()->with('success', $added > 0
            ? "{$added} barang standar ditambahkan ke inventaris kelas {$classroom->name}."
            : 'Semua barang standar sudah ada di kelas ini.');
    }

    public function destroyItem(InventoryItem $inventoryItem): RedirectResponse
    {
        Gate::authorize('manageInventory', $inventoryItem->classroom);

        // Foto sengaja tidak dihapus: masih dipakai riwayat laporan.
        $inventoryItem->delete();

        return back()->with('success', "Barang \"{$inventoryItem->name}\" dihapus dari inventaris.");
    }

    /**
     * Wali kelas mengirim kondisi terbaru semua barang. Kondisi disimpan ke
     * barangnya (untuk tampilan terkini) sekaligus dicatat sebagai riwayat.
     */
    public function storeReport(StoreInventoryReportRequest $request, Classroom $classroom): RedirectResponse
    {
        $submitted = $request->input('items', []);
        $items = $classroom->inventoryItems()->whereIn('id', array_keys($submitted))->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Belum ada barang inventaris untuk dilaporkan.']);
        }

        DB::transaction(function () use ($request, $classroom, $submitted, $items) {
            $report = $classroom->inventoryReports()->create([
                'reported_by' => $request->user()->id,
                'notes' => $request->input('notes'),
            ]);

            foreach ($items as $item) {
                $condition = [
                    'good_quantity' => (int) $submitted[$item->id]['good_quantity'],
                    'damaged_quantity' => (int) $submitted[$item->id]['damaged_quantity'],
                    'notes' => $submitted[$item->id]['notes'] ?? null,
                    'photo_path' => $request->file("photos.{$item->id}")?->store('inventory-photos', 'public') ?? $item->photo_path,
                ];

                $item->update([...$condition, 'last_reported_at' => now()]);
                $report->items()->create([
                    ...$condition,
                    'inventory_item_id' => $item->id,
                    'category' => $item->category,
                    'name' => $item->name,
                ]);
            }
        });

        return redirect()
            ->route('guru.inventaris', ['classroom' => $classroom->id])
            ->with('success', "Laporan kondisi inventaris kelas {$classroom->name} berhasil dikirim.");
    }

    public function showReport(Request $request, InventoryReport $inventoryReport): View
    {
        Gate::authorize('viewInventory', $inventoryReport->classroom);

        $inventoryReport->load(['classroom', 'reporter']);

        return view('admin.inventaris-laporan', [
            'report' => $inventoryReport,
            'itemsByCategory' => $this->groupByCategory($inventoryReport->items()->orderBy('id')->get()),
            'backRoute' => $request->routeIs('admin.*') ? 'admin.inventaris' : 'guru.inventaris',
        ]);
    }

    /**
     * Kelompokkan barang per kategori, urut sesuai urutan kategori di form.
     *
     * @param  Collection<int, InventoryItem>|Collection<int, InventoryReportItem>  $items
     * @return Collection<string, Collection<int, mixed>>
     */
    private function groupByCategory(Collection $items): Collection
    {
        return collect(InventoryCategory::cases())
            ->mapWithKeys(fn (InventoryCategory $category) => [$category->value => $items->where('category', $category)->values()])
            ->filter(fn (Collection $group) => $group->isNotEmpty());
    }
}
