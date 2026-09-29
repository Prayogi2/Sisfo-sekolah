{{-- Kolom kondisi: jumlah baik & rusak dengan ikon, plus foto kondisi. --}}
@props(['good', 'damaged', 'photoPath' => null, 'name' => ''])

<div class="d-flex align-items-center gap-3">
    <div class="small">
        <div class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Baik: {{ $good }}</div>
        <div class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Rusak: {{ $damaged }}</div>
    </div>
    @if($photoPath)
        <a href="{{ Storage::url($photoPath) }}" target="_blank" title="Lihat foto kondisi {{ $name }}">
            <img src="{{ Storage::url($photoPath) }}" alt="Foto kondisi {{ $name }}" class="rounded border inventory-photo">
        </a>
    @endif
</div>
