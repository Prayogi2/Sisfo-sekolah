@extends('layouts.app')

@section('title', 'Laporan Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
             <div class="d-flex align-items-center">
                <a href="{{ route('admin.laporan') }}" class="btn btn-light me-3 shadow-sm border" title="Kembali ke Pusat Laporan">
                    <i class="bi bi-arrow-left fs-5 text-primary"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Pembayaran SPP</h1>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button class="btn btn-outline-danger shadow-sm btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
        </div>
        <!-- Form Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Bulan</label>
                        <select class="form-select">
                            <option>Mei 2024</option>
                            <option>April 2024</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select class="form-select">
                            <option>Semua Kelas</option>
                            <option>X IPA 1</option>
                            <option>XI IPS 2</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select class="form-select">
                            <option>Semua Status</option>
                            <option>Lunas</option>
                            <option>Pending</option>
                            <option>Tunggakan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Ringkasan Keuangan -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Tagihan Keseluruhan</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp 435.750.000</div>
                        <small class="text-muted">1.245 Siswa x Rp 350.000</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Pemasukan Diterima (Lunas)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp 380.000.000</div>
                        <small class="text-muted">1.100 Siswa</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Tunggakan & Pending</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp 55.750.000</div>
                        <small class="text-muted">145 Siswa (24 Pending, 121 Tunggakan)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap SPP Siswa -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Rekap Status Pembayaran Siswa (Mei 2024)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Tagihan</th>
                                <th>Tgl Bayar</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Citra+L&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Citra Lestari</span>
                                    </div>
                                </td>
                                <td>X IPA 1</td>
                                <td>Rp 350.000</td>
                                <td>10 Mei 2024</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lunas</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-receipt text-primary"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Budi+S&background=fff8e6&color=f59e0b&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Budi Santoso</span>
                                    </div>
                                </td>
                                <td>X IPA 1</td>
                                <td>Rp 350.000</td>
                                <td>15 Mei 2024</td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Pending</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-clock text-warning"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Ahmad+F&background=ffeaea&color=dc3545&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ahmad Fadli</span>
                                    </div>
                                </td>
                                <td>XII IPA 3</td>
                                <td>Rp 350.000</td>
                                <td>-</td>
                                <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Tunggakan</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-exclamation-circle text-danger"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-3 bg-white">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
@endsection