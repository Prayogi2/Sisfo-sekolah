{{-- Riwayat laporan kondisi inventaris dari wali kelas. --}}
@props(['reports', 'routePrefix'])

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Laporan Kondisi</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Tanggal Laporan</th><th>Dilaporkan Oleh</th><th class="text-center">Baik</th><th class="text-center">Rusak</th><th>Catatan</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->created_at->translatedFormat('d F Y, H:i') }}</td>
                            <td>{{ $report->reporter?->name ?? '-' }}</td>
                            <td class="text-center text-success fw-semibold">{{ (int) $report->good_total }}</td>
                            <td class="text-center text-danger fw-semibold">{{ (int) $report->damaged_total }}</td>
                            <td class="small text-muted">{{ $report->notes ?: '-' }}</td>
                            <td class="text-end"><a href="{{ route($routePrefix.'.laporan.show', $report) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada laporan kondisi dari wali kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
