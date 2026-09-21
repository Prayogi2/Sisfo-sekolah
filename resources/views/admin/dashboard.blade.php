@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Dashboard Utama</h1>
        <!-- Toggle Kontrol Mode Keterlambatan -->
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="modeTelat" checked>
            <label class="form-check-label fw-bold text-danger" for="modeTelat">Mode Scan Keterlambatan Aktif</label>
        </div>
    </div>

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Siswa (Buku Induk)</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">245 Siswa</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Presentase Kehadiran</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">94.5%</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">SPP Pending</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">12 Siswa</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Pengajuan Izin</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">3 Pengajuan</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Grafik Kehadiran -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Rekapitulasi Kehadiran Mingguan</h6></div>
                <div class="card-body"><canvas id="chartKehadiran" height="80"></canvas></div>
            </div>
        </div>

        <!-- Grafik Donut Nilai -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Rata-rata Nilai Mata Pelajaran</h6></div>
                <div class="card-body"><canvas id="chartNilai" height="140"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Top 5 Ranking -->
    <div class="card shadow-sm">
        <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary">Top 5 Ranking Siswa Teratas</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Peringkat</th><th>Nama Siswa</th><th>Kelas</th><th>Nilai Rata-rata</th><th>Poin Prestasi</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-warning p-2"><i class="bi bi-trophy-fill"></i> Juara 1</span></td>
                            <td>Ahmad Fauzi</td><td>VI</td><td>95.5</td><td>150 Poin</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary p-2"><i class="bi bi-award-fill"></i> Juara 2</span></td>
                            <td>Siti Aminah</td><td>VI</td><td>93.0</td><td>120 Poin</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger p-2"><i class="bi bi-award-fill"></i> Juara 3</span></td>
                            <td>Budi Santoso</td><td>V</td><td>92.5</td><td>100 Poin</td>
                        </tr>
                        <tr><td>4</td><td>Citra Lestari</td><td>V</td><td>90.0</td><td>80 Poin</td></tr>
                        <tr><td>5</td><td>Eka Wahyuni</td><td>IV</td><td>89.5</td><td>75 Poin</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Chart Kehadiran (Bar/Line)
    const ctxHadir = document.getElementById('chartKehadiran').getContext('2d');
    new Chart(ctxHadir, {
        type: 'bar',
        data: {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            datasets: [
                { label: 'Hadir', data: [230, 235, 240, 232, 228], backgroundColor: 'rgba(13, 110, 253, 0.7)' },
                { label: 'Telat', data: [5, 3, 2, 6, 4], backgroundColor: 'rgba(255, 193, 7, 0.7)' },
                { label: 'Alpa', data: [10, 7, 3, 7, 13], backgroundColor: 'rgba(220, 53, 69, 0.7)' }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Chart Nilai (Donut)
    const ctxNilai = document.getElementById('chartNilai').getContext('2d');
    new Chart(ctxNilai, {
        type: 'doughnut',
        data: {
            labels: ['Akademik (A)', 'Akademik (B)', 'Tahfidz', 'Non-Akademik'],
            datasets: [{ data: [45, 30, 15, 10], backgroundColor: ['#0d6efd', '#85affb', '#ffc107', '#20c997'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush