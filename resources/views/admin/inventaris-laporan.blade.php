@extends('layouts.app')

@section('title', 'Detail Laporan Inventaris')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Laporan Inventaris Kelas {{ $report->classroom->name }}</h1>
            <p class="text-muted mb-0">Dikirim {{ $report->created_at->translatedFormat('d F Y, H:i') }} oleh {{ $report->reporterLabelFor(auth()->user()) }}</p>
        </div>
        <a href="{{ route($backRoute, ['classroom' => $report->classroom_id]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    @if($report->notes)
        <div class="alert alert-light border"><i class="bi bi-chat-left-text me-1"></i><strong>Catatan wali kelas:</strong> {{ $report->notes }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th style="width: 50px;">No</th><th>Nama Barang</th><th class="text-center" style="width: 80px;">Jumlah</th><th>Kondisi</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>
                        @forelse($itemsByCategory as $categoryValue => $items)
                            <tr class="table-primary"><td colspan="5" class="fw-bold">{{ \App\Enums\InventoryCategory::from($categoryValue)->label() }}</td></tr>
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-center fw-bold">{{ $item->good_quantity + $item->damaged_quantity }}</td>
                                    <td><x-inventaris.condition :good="$item->good_quantity" :damaged="$item->damaged_quantity" :photo-path="$item->photo_path" :name="$item->name" /></td>
                                    <td class="small text-muted">{{ $item->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Laporan ini tidak berisi barang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .inventory-photo { width: 56px; height: 56px; object-fit: cover; }
</style>
@endpush
