@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard Utama</h1>
            <button class="btn btn-primary"><i class="bi bi-download me-1"></i> Export Laporan Harian</button>
        </div>

        <!-- Metric Cards -->
        <div class="row mb-4">
            <!-- Total Siswa -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Siswa</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">1,245</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people-fill fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kehadiran Hari Ini -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Hadir Hari Ini</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">1,102 (88%)</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-person-check-fill fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SPP Pending -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">SPP Menunggu Verifikasi</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">24 Siswa</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-cash-coin fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pengajuan Izin -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">Pengajuan Izin/Sakit</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">8 Pengajuan</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-envelope-paper-heart fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Grafik Absensi -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Statistik Kehadiran Mingguan</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Verifikasi SPP Terbaru -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Verifikasi SPP Terbaru</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Budi Santoso</span><br>
                                    <small class="text-muted">Kelas X IPA 1 - Rp 350.000</small>
                                </div>
                                <span class="badge bg-warning rounded-pill">Pending</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Siti Nurhaliza</span><br>
                                    <small class="text-muted">Kelas XI IPS 2 - Rp 350.000</small>
                                </div>
                                <span class="badge bg-warning rounded-pill">Pending</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Ahmad Fadli</span><br>
                                    <small class="text-muted">Kelas XII IPA 3 - Rp 400.000</small>
                                </div>
                                <span class="badge bg-success rounded-pill">Lunas</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Dewi Persik</span><br>
                                    <small class="text-muted">Kelas X IPA 1 - Rp 350.000</small>
                                </div>
                                <span class="badge bg-danger rounded-pill">Ditolak</span>
                            </li>
                        </ul>
                        <div class="text-center mt-3 mb-3">
                            <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Chart.js Script for Attendance Graph
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                datasets: [{
                    label: 'Hadir',
                    data: [1150, 1200, 1120, 1180, 1102, 1050],
                    borderColor: 'rgba(28, 177, 68, 1)',
                    backgroundColor: 'rgba(28, 177, 68, 0.1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Izin / Sakit',
                    data: [30, 20, 45, 25, 40, 60],
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Alpa',
                    data: [65, 25, 80, 40, 103, 135],
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
@endpush