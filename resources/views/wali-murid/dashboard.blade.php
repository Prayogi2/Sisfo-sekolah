@extends('layouts.app')

@section('title', 'Dashboard Wali Murid')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading & Pilih Anak -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <img src="https://ui-avatars.com/api/?name=Budi+Santoso&size=100&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-3 shadow-sm" width="70" height="70" alt="Foto Anak" style="border: 3px solid #0d6efd;">
                    <div>
                        <span class="badge bg-primary-soft text-primary mb-1">X IPA 1 - Siswa Aktif</span>
                        <h4 class="mb-0 fw-bold text-dark">Budi Santoso</h4>
                        <small class="text-muted">NIS: 1002456 | Wali: Bapak Andi Santoso</small>
                    </div>
                </div>
                <div class="text-md-end">
                    <label class="form-label small text-muted mb-0">Pantau Anak Lain:</label>
                    <select class="form-select form-select-sm mt-1">
                        <option selected>Budi Santoso (Kelas X)</option>
                        <option>Citra Lestari (Kelas VIII)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row mb-4">
            <!-- Kehadiran Bulan Ini -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1 text-success">Kehadiran Bulan Ini</div>
                                <div class="h5 mb-0 fw-bold text-dark">18 Hari Hadir</div>
                                <small class="text-warning"><i class="bi bi-exclamation-circle"></i> 1x Telat</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #e6f9ee;">
                                <i class="bi bi-calendar-check fs-4 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status SPP -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1 text-danger">SPP Bulan Mei</div>
                                <div class="h5 mb-0 fw-bold text-dark">Rp 350.000</div>
                                <small class="text-danger">Jatuh tempo: 20 Mei</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #ffeaea;">
                                <i class="bi bi-cash-coin fs-4 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nilai Kuis Terbaru -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1 text-primary">Kuis Terbaru</div>
                                <div class="h5 mb-0 fw-bold text-dark">Matematika 85</div>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> Naik 5 poin</small>
                            </div>
                            <div class="p-3 rounded-3 bg-primary-soft" style="background-color: #e7f1ff;">
                                <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peringkat Kelas -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1 text-warning">Peringkat Kelas</div>
                                <div class="h5 mb-0 fw-bold text-dark">Top 4 dari 36</div>
                                <small class="text-muted">X IPA 1</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #fff8e6;">
                                <i class="bi bi-trophy-fill fs-4 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pengumuman Sekolah -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-megaphone-fill me-2"></i>Pengumuman Sekolah Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-4 pb-3 border-bottom">
                            <div class="bg-primary-soft p-3 rounded-3 me-3" style="background-color: #e7f1ff;">
                                <i class="bi bi-calendar-event fs-4 text-primary"></i>
                            </div>
                            <div>
                                <span class="badge bg-danger-soft text-danger mb-1" style="background-color: #ffeaea;">Penting</span>
                                <h6 class="fw-bold text-dark mb-1">Persiapan Ujian Tengah Semester (UTS)</h6>
                                <p class="text-muted small mb-1">UTS akan dimulai pada tanggal 25 Mei 2024. Pastikan anak-anak belajar dengan giat. Jadwal lengkap dapat diunduh pada menu akademik.</p>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i> 15 Mei 2024, 09:00 WIB</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="p-3 rounded-3 me-3" style="background-color: #e6f9ee;">
                                <i class="bi bi-cash-stack fs-4 text-success"></i>
                            </div>
                            <div>
                                <span class="badge bg-success-soft text-success mb-1" style="background-color: #e6f9ee;">Keuangan</span>
                                <h6 class="fw-bold text-dark mb-1">Pembayaran SPP Bulan Mei</h6>
                                <p class="text-muted small mb-1">Ingatkan pembayaran SPP paling lambat tanggal 20 Mei 2024 untuk menghindari keterlambatan.</p>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i> 10 Mei 2024, 14:30 WIB</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kuis Mendatang -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-week me-2"></i>Agenda Mendatang</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">Kuis Fisika</span><br>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> 22 Mei 2024, 10:00</small>
                                </div>
                                <span class="badge bg-primary-soft text-primary">Kuis</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">Pengumpulan Tugas Biologi</span><br>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> 24 Mei 2024, 23:59</small>
                                </div>
                                <span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Tugas</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">Libur Hari Raya</span><br>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i> 27 Mei 2024</small>
                                </div>
                                <span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Libur</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection