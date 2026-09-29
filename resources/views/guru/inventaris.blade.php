@extends('layouts.app')

@section('title', 'Inventaris Kelas')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Inventaris Kelas</h1>
            <p class="text-muted mb-0">Laporkan kondisi barang inventaris kelas yang Anda walikan.</p>
        </div>
        @if($classrooms->count() > 1)
            <form method="GET" action="{{ route('guru.inventaris') }}">
                <select name="classroom" class="form-select fw-semibold" onchange="this.form.submit()" aria-label="Pilih kelas">
                    @foreach($classrooms as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $classroom?->id)>Kelas {{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(! $classroom)
        <div class="alert alert-light border text-muted"><i class="bi bi-info-circle me-1"></i>Anda belum ditugaskan sebagai wali kelas, jadi belum ada inventaris kelas untuk dilaporkan.</div>
    @else
        <x-page-guide>Isi jumlah barang yang <strong>baik</strong> dan <strong>rusak</strong>, lampirkan foto kondisinya bila perlu, lalu klik <strong>Kirim Laporan Kondisi</strong>. Barang yang belum ada di daftar bisa ditambahkan di bawah ini.</x-page-guide>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <p class="small text-muted mb-3"><i class="bi bi-door-open me-1"></i>Kelas <strong class="text-dark">{{ $classroom->name }}</strong> · Laporan terakhir: <strong class="text-dark">{{ $reports->first()?->created_at->translatedFormat('d F Y, H:i') ?? 'Belum ada' }}</strong></p>

        <x-inventaris.add-item-form :classroom="$classroom" :categories="$categories" route-prefix="guru.inventaris" />

        <form action="{{ route('guru.inventaris.laporan.store', $classroom) }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm mb-4">
            @csrf
            <div class="card-header bg-white fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Laporan Kondisi Inventaris</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Barang</th>
                                <th style="width: 110px;"><i class="bi bi-check-circle-fill text-success me-1"></i>Baik</th>
                                <th style="width: 110px;"><i class="bi bi-x-circle-fill text-danger me-1"></i>Rusak</th>
                                <th class="text-center" style="width: 80px;">Jumlah</th>
                                <th style="min-width: 220px;">Foto Kondisi</th>
                                <th style="min-width: 200px;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemsByCategory as $categoryValue => $items)
                                <tr class="table-primary"><td colspan="7" class="fw-bold">{{ \App\Enums\InventoryCategory::from($categoryValue)->label() }}</td></tr>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td><input type="number" name="items[{{ $item->id }}][good_quantity]" value="{{ old("items.{$item->id}.good_quantity", $item->good_quantity) }}" min="0" max="9999" class="form-control form-control-sm" required aria-label="Jumlah {{ $item->name }} kondisi baik"></td>
                                        <td><input type="number" name="items[{{ $item->id }}][damaged_quantity]" value="{{ old("items.{$item->id}.damaged_quantity", $item->damaged_quantity) }}" min="0" max="9999" class="form-control form-control-sm" required aria-label="Jumlah {{ $item->name }} rusak"></td>
                                        <td class="text-center fw-bold item-total">{{ (int) old("items.{$item->id}.good_quantity", $item->good_quantity) + (int) old("items.{$item->id}.damaged_quantity", $item->damaged_quantity) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($item->photo_path)
                                                    <a href="{{ Storage::url($item->photo_path) }}" target="_blank"><img src="{{ Storage::url($item->photo_path) }}" alt="Foto kondisi {{ $item->name }}" class="rounded border inventory-photo"></a>
                                                @endif
                                                <input type="file" name="photos[{{ $item->id }}]" accept="image/*" capture="environment" class="form-control form-control-sm" aria-label="Foto kondisi {{ $item->name }}">
                                            </div>
                                        </td>
                                        <td><input type="text" name="items[{{ $item->id }}][notes]" value="{{ old("items.{$item->id}.notes", $item->notes) }}" maxlength="500" class="form-control form-control-sm" placeholder="mis. 2 kaki kursi patah"></td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada barang inventaris. Klik <strong>Tambah Daftar Barang Standar</strong> di atas untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($itemsByCategory->isNotEmpty())
                <div class="card-footer bg-white">
                    <label class="form-label small text-muted mb-1">Catatan untuk admin (opsional)</label>
                    <textarea name="notes" rows="2" maxlength="1000" class="form-control mb-3" placeholder="mis. Mohon penggantian 2 lampu yang mati">{{ old('notes') }}</textarea>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Foto yang tidak diganti tetap memakai foto sebelumnya.</small>
                        <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Kirim Laporan Kondisi</button>
                    </div>
                </div>
            @endif
        </form>

        <x-inventaris.report-history :reports="$reports" route-prefix="guru.inventaris" />
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Kolom Jumlah = baik + rusak, ikut berubah saat wali kelas mengetik.
    document.querySelectorAll('input[name$="[good_quantity]"], input[name$="[damaged_quantity]"]').forEach(input => {
        input.addEventListener('input', function () {
            const row = input.closest('tr');
            const value = name => parseInt(row.querySelector(`input[name$="[${name}]"]`).value, 10) || 0;
            row.querySelector('.item-total').textContent = value('good_quantity') + value('damaged_quantity');
        });
    });
</script>
@endpush

@push('styles')
<style>
    .inventory-photo { width: 48px; height: 48px; object-fit: cover; }
</style>
@endpush
