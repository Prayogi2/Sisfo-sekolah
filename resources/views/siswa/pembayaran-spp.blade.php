@extends('layouts.app')

@section('title', 'Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Informasi & Pembayaran SPP</h1>
        </div>

        <div class="row">
            <!-- Kolom Kiri: Riwayat Pembayaran -->
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary">Riwayat Pembayaran SPP</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bulan / Tahun</th>
                                        <th>Nominal</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>April 2024</td>
                                        <td>Rp 350.000</td>
                                        <td>10 Apr 2024</td>
                                        <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lunas</span></td>
                                        <td class="text-center"><button class="btn btn-sm btn-light" title="Lihat Bukti"><i class="bi bi-receipt text-primary"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>Maret 2024</td>
                                        <td>Rp 350.000</td>
                                        <td>15 Mar 2024</td>
                                        <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lunas</span></td>
                                        <td class="text-center"><button class="btn btn-sm btn-light" title="Lihat Bukti"><i class="bi bi-receipt text-primary"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>Februari 2024</td>
                                        <td>Rp 350.000</td>
                                        <td>20 Feb 2024</td>
                                        <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Pending</span></td>
                                        <td class="text-center"><button class="btn btn-sm btn-light" title="Menunggu Verifikasi" disabled><i class="bi bi-hourglass-split text-warning"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>Januari 2024</td>
                                        <td>Rp 350.000</td>
                                        <td>-</td>
                                        <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Ditolak</span></td>
                                        <td class="text-center"><button class="btn btn-sm btn-light" title="Upload Ulang"><i class="bi bi-upload text-danger"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Info Rek & Form Upload -->
            <div class="col-lg-4 col-md-12">
                <!-- Info Rekening -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                        <h6 class="m-0 fw-bold"><i class="bi bi-bank me-2"></i>Rekening Tujuan Sekolah</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=BCA&background=0d6efd&color=fff&bold=true" class="me-3" width="50" height="50" alt="Bank">
                            <div>
                                <small class="text-muted d-block">Bank BCA</small>
                                <h5 class="mb-0 fw-bold text-dark">1234567890</h5>
                            </div>
                        </div>
                        <hr>
                        <p class="mb-1">Atas Nama : <strong>Yayasan Nurul Falaq</strong></p>
                        <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> Pastikan nominal transfer sesuai dengan tagihan.</p>
                    </div>
                </div>

                <!-- Form Upload Bukti -->
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-cloud-upload me-2"></i>Upload Bukti Transfer</h6>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pilih Bulan Tagihan</label>
                                <select class="form-select">
                                    <option>Mei 2024 - Rp 350.000</option>
                                    <option>Juni 2024 - Rp 350.000</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Bukti Pembayaran (Foto Struk)</label>
                                <input type="file" class="form-control" accept="image/png, image/jpeg, application/pdf">
                                <small class="text-muted">Format: PNG, JPG, PDF (Max 2MB)</small>
                            </div>
                            <div class="alert alert-info d-flex p-2" role="alert">
                                <i class="bi bi-info-circle me-2 mt-1"></i>
                                <small>Pembayaran akan diverifikasi oleh Admin selama 1x24 Jam.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="bi bi-send me-1"></i> Kirim Bukti Bayar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection