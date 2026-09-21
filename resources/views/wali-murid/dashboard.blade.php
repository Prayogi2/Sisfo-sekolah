@extends('layouts.app')

@section('title', 'Dashboard Wali Murid')

@section('content')
    <div class="container-fluid">
        
        <!-- 1. Banner Profil Anak -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="row g-0 align-items-center" style="background: linear-gradient(135deg, #e7f1ff 0%, #f4f7fe 100%);">
                <div class="col-md-2 text-center p-4">
                    <img src="https://ui-avatars.com/api/?name=Budi+Santoso&size=150&background=0d6efd&color=fff&bold=true" class="rounded-circle shadow-sm border border-4 border-white" width="110" height="110" alt="Foto Anak">
                </div>
                <div class="col-md-7 p-4">
                    <span class="badge bg-primary-soft text-primary mb-2"><i class="bi bi-mortarboard-fill me-1"></i> Kelas 6-A</span>
                    <h3 class="fw-bold text-dark mb-1">Budi Santoso</h3>
                    <div class="row text-muted small mt-3">
                        <div class="col-md-6 mb-2">
                            <i class="bi bi-person-badge me-2 text-primary"></i> NISN: <span class="fw-semibold text-dark">0098761234</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <i class="bi bi-person-vcard me-2 text-primary"></i> Wali Kelas: <span class="fw-semibold text-dark">Ustadz Ali, S.Pd</span>
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-telephone me-2 text-primary"></i> No. Ortu: <span class="fw-semibold text-dark">081234567890</span>
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> Tahun Ajaran: <span class="fw-semibold text-dark">2023/2024</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 p-4 border-start d-flex flex-column justify-content-center text-md-end">
                    <small class="text-muted text-uppercase fw-bold mb-1">Status Akademik</small>
                    <h5 class="text-success fw-bold mb-0"><i class="bi bi-patch-check-fill me-1"></i> Aktif</h5>
                    <small class="text-muted mt-2">Semester Genap</small>
                </div>
            </div>
        </div>

        <!-- 2. Widget Performa & Aktivitas Ringkas -->
        <div class="row mb-4">
            <!-- Persentase Kehadiran -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Kehadiran Bulan Ini</div>
                                <div class="h4 mb-0 fw-bold text-dark">95% Hadir</div>
                                <small class="text-muted">18 Hadir | 1 Izin | 0 Alpa</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #e6f9ee;">
                                <i class="bi bi-calendar-check-fill fs-3 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nilai Rata-Rata & Ranking -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Performa Kuis CBT</div>
                                <div class="h4 mb-0 fw-bold text-dark">Nilai 88.5</div>
                                <small class="text-warning fw-semibold"><i class="bi bi-trophy-fill me-1"></i> Peringkat 3 dari 30 Siswa</small>
                            </div>
                            <div class="p-3 rounded-3 bg-primary-soft" style="background-color: #e7f1ff;">
                                <i class="bi bi-graph-up-arrow fs-3 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status SPP -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">Status SPP Mei 2024</div>
                                <div class="h4 mb-0 fw-bold text-dark">Rp 150.000</div>
                                <small class="text-danger">Tunggakan (Jatuh tempo 20 Mei)</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #ffeaea;">
                                <i class="bi bi-cash-coin fs-3 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Kolom Kiri: Visualisasi Grafis -->
            <div class="col-lg-8 col-md-12 mb-4">
                <!-- Grafik Nilai Kuis -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-bar-chart-line-fill me-2"></i>Perkembangan Nilai Kuis Bulanan</h6>
                        <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;"><i class="bi bi-arrow-up"></i> Meningkat 5%</span>
                    </div>
                    <div class="card-body">
                        <canvas id="chartNilaiAnak" height="100"></canvas>
                    </div>
                </div>

                <!-- Progress Hafalan & Akademik -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-bullseye me-2"></i>Target Akademik & Hafalan</h6>
                    </div>
                    <div class="card-body">
                        <!-- Progress Hafalan -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-dark"><i class="bi bi-book-fill text-success me-2"></i>Hafalan Juz 30 (Juz 'Amma)</span>
                                <span class="text-muted small">75% (22 / 30 Surat)</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <!-- Progress Nilai Akademik -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-dark"><i class="bi bi-calculator-fill text-primary me-2"></i>Target Nilai Matematika (Min 85)</span>
                                <span class="text-muted small">88% Tercapai</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 88%;" aria-valuenow="88" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Timeline Aktivitas -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Timeline Aktivitas Anak</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <!-- Item 1 -->
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-success-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #e6f9ee;">
                                        <i class="bi bi-person-check-fill text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark">Presensi Masuk Sekolah</h6>
                                    <p class="text-muted small mb-1">Hadir tepat waktu pukul 06:45 WIB.</p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> Hari ini, 06:45 WIB</small>
                                </div>
                            </div>
                            <!-- Item 2 -->
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-primary-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #e7f1ff;">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark">Mengerjakan Kuis Matematika</h6>
                                    <p class="text-muted small mb-1">Selesai mengerjakan Kuis Aljabar dengan nilai <strong class="text-primary">90</strong>.</p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> Kemarin, 10:15 WIB</small>
                                </div>
                            </div>
                            <!-- Item 3 -->
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-success-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #e6f9ee;">
                                        <i class="bi bi-cash-coin text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark">Pembayaran SPP Diverifikasi</h6>
                                    <p class="text-muted small mb-1">Pembayaran SPP April 2024 telah diverifikasi Admin.</p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> 2 Hari yang lalu</small>
                                </div>
                            </div>
                            <!-- Item 4 -->
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-warning-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #fff8e6;">
                                        <i class="bi bi-trophy-fill text-warning"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark">Mendapat Poin Prestasi</h6>
                                    <p class="text-muted small mb-1">Juara 1 Lomba Tahfidz Tingkat Kecamatan (+50 Poin).</p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> 5 Hari yang lalu</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Inisialisasi Grafik Perkembangan Nilai Anak
        const ctxNilai = document.getElementById('chartNilaiAnak').getContext('2d');
        
        // Gradient warna untuk grafik
        const gradient = ctxNilai.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(13, 110, 253, 0.5)');
        gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

        const chartNilaiAnak = new Chart(ctxNilai, {
            type: 'line',
            data: {
                labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei'],
                datasets: [{
                    label: 'Nilai Kuis',
                    data: [80, 82, 85, 87, 88.5],
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: gradient,
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointBackgroundColor: 'white',
                    pointBorderColor: 'rgba(13, 110, 253, 1)',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false // Sembunyikan legend karena hanya 1 dataset
                    },
                    tooltip: {
                        backgroundColor: '#0d6efd',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Nilai: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 70,
                        max: 100,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
@endpush