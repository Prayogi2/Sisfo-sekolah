@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Selamat Datang, Ahmad Fauzi!</h1>
                <p class="text-muted mb-0">Kelas 6-A - MIS Nurul Falaq</p>
            </div>
            <span class="badge bg-success-soft text-success p-2 shadow-sm" style="background-color: #e6f9ee;">
                <i class="bi bi-calendar-check me-1"></i> Senin, 20 Mei 2024
            </span>
        </div>

        <!-- Statistik Ringkas -->
        <div class="row mb-4">
            <!-- Status Absensi -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Absensi Hari Ini</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Hadir (06:45 WIB)</div>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #e6f9ee;">
                                <i class="bi bi-emoji-smile fs-4 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tagihan SPP -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">SPP Pending</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">Rp 350.000</div>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #ffeaea;">
                                <i class="bi bi-cash-coin fs-4 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kuis Mendatang -->
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Kuis Mendatang</div>
                                <div class="h5 mb-0 fw-bold text-gray-800">2 Kuis</div>
                            </div>
                            <div class="p-3 rounded-3 bg-primary-soft" style="background-color: #e7f1ff;">
                                <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Kolom Kiri -->
            <div class="col-lg-8 col-md-12 mb-4">
                <!-- Kuis Mendatang -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-card-checklist me-2"></i>Kuis Mendatang</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary-soft p-3 rounded-3 me-3 text-center" style="background-color: #e7f1ff; width: 60px;">
                                        <span class="fw-bold text-primary d-block fs-5">22</span>
                                        <small class="text-primary">Mei</small>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">Kuis Matematika Bab 3</span>
                                        <p class="mb-0 text-muted small"><i class="bi bi-clock me-1"></i> 10:00 WIB • Durasi 30 Menit</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i> Kerjakan</button>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="p-3 rounded-3 me-3 text-center" style="background-color: #e6f9ee; width: 60px;">
                                        <span class="fw-bold text-success d-block fs-5">24</span>
                                        <small class="text-success">Mei</small>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">Kuis Al-Qur'an Hadits</span>
                                        <p class="mb-0 text-muted small"><i class="bi bi-clock me-1"></i> 08:00 WIB • Durasi 20 Menit</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary disabled">Belum Dimulai</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Absensi -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-week me-2"></i>Ringkasan Kehadiran Bulan Ini</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-3 border-end">
                                <h4 class="fw-bold text-success">18</h4>
                                <small class="text-muted">Hadir</small>
                            </div>
                            <div class="col-3 border-end">
                                <h4 class="fw-bold text-warning">1</h4>
                                <small class="text-muted">Telat</small>
                            </div>
                            <div class="col-3 border-end">
                                <h4 class="fw-bold text-info">1</h4>
                                <small class="text-muted">Izin</small>
                            </div>
                            <div class="col-3">
                                <h4 class="fw-bold text-danger">0</h4>
                                <small class="text-muted">Alpa</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan (SPP & Info) -->
            <div class="col-lg-4 col-md-12">
                <!-- Banner SPP -->
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Tagihan SPP Mei 2024</h6>
                        <h3 class="fw-bold mb-0">Rp 350.000</h3>
                        <p class="small mb-3 opacity-75">Jatuh tempo: 20 Mei 2024</p>
                        <a href="#" class="btn btn-light btn-sm w-100 fw-semibold text-primary">Bayar Sekarang</a>
                    </div>
                </div>

                <!-- Jadwal Hari Ini -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Jadwal Pelajaran Hari Ini</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>07.00 - 08.30</span>
                                <span class="fw-semibold">Pendidikan Agama Islam</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>08.30 - 10.00</span>
                                <span class="fw-semibold">Matematika</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>10.30 - 12.00</span>
                                <span class="fw-semibold">Bahasa Indonesia</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection