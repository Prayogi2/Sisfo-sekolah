@extends('layouts.app')

@section('title', 'Inventaris Kelas')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Inventaris Kelas</h1>
            <p class="text-muted mb-0">Kelola daftar barang tiap kelas dan pantau laporan kondisi dari wali kelas.</p>
        </div>
        @if($classrooms->isNotEmpty())
            <form method="GET" action="{{ route('admin.inventaris') }}">
                <select name="classroom" class="form-select fw-semibold" onchange="this.form.submit()" aria-label="Pilih kelas">
                    @foreach($classrooms as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $classroom?->id)>Kelas {{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <x-page-guide>Pilih kelas, lalu tambah atau hapus barang inventarisnya. Kondisi barang (jumlah baik/rusak & foto) diisi oleh <strong>wali kelas</strong> lewat menu Inventaris Kelas di akun guru.</x-page-guide>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if(! $classroom)
        <div class="alert alert-light border text-muted">Belum ada kelas. Buat kelas dulu di menu <a href="{{ route('admin.pembagian-kelas') }}">Pembagian Kelas</a>.</div>
    @else
        <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
            <span><i class="bi bi-person-badge me-1"></i>Wali kelas: <strong class="text-dark">{{ $classroom->homeroomTeacher?->name ?? 'Belum ditentukan' }}</strong></span>
            <span><i class="bi bi-clock me-1"></i>Laporan terakhir: <strong class="text-dark">{{ $reports->first()?->created_at->translatedFormat('d F Y, H:i') ?? 'Belum ada' }}</strong></span>
        </div>

        <x-inventaris.add-item-form :classroom="$classroom" :categories="$categories" route-prefix="admin.inventaris" />

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th style="width: 50px;">No</th><th>Nama Barang</th><th>Kondisi</th><th>Keterangan</th><th>Dilaporkan</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse($itemsByCategory as $categoryValue => $items)
                                <tr class="table-primary"><td colspan="6" class="fw-bold">{{ \App\Enums\InventoryCategory::from($categoryValue)->label() }}</td></tr>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td><x-inventaris.condition :good="$item->good_quantity" :damaged="$item->damaged_quantity" :photo-path="$item->photo_path" :name="$item->name" /></td>
                                        <td class="small text-muted">{{ $item->notes ?: '-' }}</td>
                                        <td class="small text-muted">{{ $item->last_reported_at?->translatedFormat('d M Y') ?? 'Belum' }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('admin.inventaris.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus barang {{ addslashes($item->name) }} dari inventaris kelas {{ addslashes($classroom->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus barang"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada barang inventaris. Klik <strong>Tambah Daftar Barang Standar</strong> untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <x-inventaris.report-history :reports="$reports" route-prefix="admin.inventaris" />
    @endif
</div>
@endsection

@push('styles')
<style>
    .inventory-photo { width: 56px; height: 56px; object-fit: cover; }
</style>
@endpush
