@extends('layouts.app')

@section('title', 'Laporan Absensi & Tata Tertib')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.laporan') }}" class="btn btn-light me-3 shadow-sm border" title="Kembali ke Pusat Laporan">
                    <i class="bi bi-arrow-left fs-5 text-primary"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Kehadiran Siswa</h1>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button class="btn btn-outline-danger shadow-sm btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
            </div>

        <!-- Control Bar Admin -->
        <div class="card shadow-sm mb-4 border-start border-danger border-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <i class="bi bi-shield-fill-exclamation fs-3 text-danger me-3"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Pengaturan Sistem Presensi</h6>
                        <small class="text-muted">Jika sakelar dimatikan, siswa yang datang terlambat akan langsung dicatat sebagai <strong>Alpa</strong>.</small>
                    </div>
                </div>
                <div class="form-check form-switch form-switch-lg d-flex align-items-center" style="transform: scale(1.2);">
                    <input class="form-check-input me-2" type="checkbox" role="switch" id="sakelarTelat" checked>
                    <label class="form-check-label fw-bold text-danger" for="sakelarTelat">Scan Keterlambatan Aktif</label>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Mulai</label>
                        <input type="date" class="form-control" value="2024-05-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Selesai</label>
                        <input type="date" class="form-control" value="2024-05-31">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select class="form-select">
                            <option>Semua Kelas</option>
                            <option>Kelas VI</option>
                            <option>Kelas V</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Hadir Tepat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">1.100 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-warning border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Telat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">45 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Izin / Sakit</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">30 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-danger border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Alpa</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">15 Siswa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Rekapitulasi Kehadiran (Mei 2024)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Hari, Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jam Scan Masuk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=A+Fauzi&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ahmad Fauzi</span>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>06:45:12 WIB</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Hadir Tepat</span></td>
                            </tr>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=S+Aminah&background=fff8e6&color=f59e0b&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Siti Aminah</span>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>07:20:00 WIB</td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Telat</span></td>
                            </tr>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=B+Santoso&background=ffeaea&color=dc3545&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Budi Santoso</span>
                                    </div>
                                </td>
                                <td>V</td>
                                <td><span class="text-muted">Tidak Scan</span></td>
                                <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Alpa</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection@extends('layouts.app')

@section('title', 'Laporan Absensi & Tata Tertib')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Kehadiran Siswa</h1>
            <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Rekap</button>
        </div>

        <!-- Control Bar Admin -->
        <div class="card shadow-sm mb-4 border-start border-danger border-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <i class="bi bi-shield-fill-exclamation fs-3 text-danger me-3"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Pengaturan Sistem Presensi</h6>
                        <small class="text-muted">Jika sakelar dimatikan, siswa yang datang terlambat akan langsung dicatat sebagai <strong>Alpa</strong>.</small>
                    </div>
                </div>
                <div class="form-check form-switch form-switch-lg d-flex align-items-center" style="transform: scale(1.2);">
                    <input class="form-check-input me-2" type="checkbox" role="switch" id="sakelarTelat" checked>
                    <label class="form-check-label fw-bold text-danger" for="sakelarTelat">Scan Keterlambatan Aktif</label>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Mulai</label>
                        <input type="date" class="form-control" value="2024-05-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Selesai</label>
                        <input type="date" class="form-control" value="2024-05-31">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select class="form-select">
                            <option>Semua Kelas</option>
                            <option>Kelas VI</option>
                            <option>Kelas V</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Hadir Tepat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">1.100 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-warning border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Telat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">45 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Izin / Sakit</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">30 Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-danger border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Alpa</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">15 Siswa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Rekapitulasi Kehadiran (Mei 2024)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Hari, Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jam Scan Masuk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=A+Fauzi&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ahmad Fauzi</span>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>06:45:12 WIB</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Hadir Tepat</span></td>
                            </tr>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=S+Aminah&background=fff8e6&color=f59e0b&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Siti Aminah</span>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>07:20:00 WIB</td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Telat</span></td>
                            </tr>
                            <tr>
                                <td>Senin, 20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=B+Santoso&background=ffeaea&color=dc3545&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Budi Santoso</span>
                                    </div>
                                </td>
                                <td>V</td>
                                <td><span class="text-muted">Tidak Scan</span></td>
                                <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Alpa</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection