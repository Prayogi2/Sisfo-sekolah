{{-- Rekap kehadiran per siswa untuk bulan / semester terpilih. --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('guru.absensi-kelas') }}" class="row g-2 align-items-end" id="recapForm">
            <input type="hidden" name="classroom" value="{{ $classroom->id }}">
            <input type="hidden" name="tampilan" value="rekap">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Periode</label>
                <select name="periode" class="form-select" id="recapType">
                    <option value="bulan" @selected($period['type'] === 'bulan')>Per Bulan</option>
                    <option value="semester" @selected($period['type'] === 'semester')>Per Semester</option>
                </select>
            </div>
            <div class="col-md-3 recap-month">
                <label class="form-label small text-muted mb-1">Bulan</label>
                <input type="month" name="bulan" value="{{ $period['month'] }}" max="{{ now()->format('Y-m') }}" class="form-control">
            </div>
            <div class="col-md-2 recap-semester">
                <label class="form-label small text-muted mb-1">Semester</label>
                <select name="semester" class="form-select">
                    @foreach(\App\Enums\Semester::cases() as $semester)
                        <option value="{{ $semester->value }}" @selected($period['semester'] === $semester)>{{ $semester->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 recap-semester">
                <label class="form-label small text-muted mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran" class="form-select">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($period['academic_year'] === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Tampilkan</button></div>
        </form>
    </div>
</div>

@php
    $exportQuery = ['classroom' => $classroom->id, 'tampilan' => 'rekap', 'periode' => $period['type'], 'bulan' => $period['month'], 'semester' => $period['semester']->value, 'tahun_ajaran' => $period['academic_year']];
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="fw-bold">Rekap Kehadiran Kelas {{ $classroom->name }} · {{ $period['label'] }}</div>
        <div class="d-flex gap-2">
            <a href="{{ route('guru.absensi-kelas.unduh', $exportQuery + ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            <a href="{{ route('guru.absensi-kelas.unduh', $exportQuery + ['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr><th style="width: 50px;">No</th><th class="text-start">Nama Siswa</th><th>NIS</th><th class="text-success">Hadir</th><th class="text-warning">Telat</th><th class="text-info">Izin/Sakit</th><th class="text-danger">Alpa</th><th>Hari Tercatat</th><th>% Kehadiran</th></tr>
                </thead>
                <tbody>
                    @forelse($recapRows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-start fw-semibold">{{ $row['name'] }}</td>
                            <td class="text-muted">{{ $row['nis'] ?: '-' }}</td>
                            <td>{{ $row['hadir'] }}</td>
                            <td>{{ $row['telat'] }}</td>
                            <td>{{ $row['izin'] }}</td>
                            <td>{{ $row['alpa'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td class="fw-bold {{ $row['percent'] !== null && $row['percent'] < 80 ? 'text-danger' : '' }}">{{ $row['percent'] === null ? '-' : $row['percent'].'%' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted py-4">Belum ada siswa aktif di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white small text-muted">% Kehadiran = (Hadir + Telat) ÷ hari tercatat. Persentase di bawah 80% ditandai merah.</div>
</div>

@push('scripts')
<script>
    (function () {
        const type = document.getElementById('recapType');
        const apply = () => {
            document.querySelectorAll('.recap-month').forEach(el => el.classList.toggle('d-none', type.value !== 'bulan'));
            document.querySelectorAll('.recap-semester').forEach(el => el.classList.toggle('d-none', type.value !== 'semester'));
        };
        type.addEventListener('change', apply);
        apply();
    })();
</script>
@endpush
