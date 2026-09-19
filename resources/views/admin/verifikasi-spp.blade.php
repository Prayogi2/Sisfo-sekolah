@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Verifikasi Pembayaran SPP</h1>
            <div class="btn-group">
                <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button class="btn btn-outline-danger shadow-sm btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
        </div>

        <!-- Tabel Verifikasi -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Daftar Bukti Transfer</h6>
                <span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">2 Menunggu Verifikasi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Upload</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Periode</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>15 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Budi+S&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Budi Santoso</span>
                                    </div>
                                </td>
                                <td>X IPA 1</td>
                                <td>Mei 2024</td>
                                <td class="fw-bold">Rp 350.000</td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Pending</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalPreview" title="Lihat Bukti">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>14 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Siti+N&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Siti Nurhaliza</span>
                                    </div>
                                </td>
                                <td>XI IPS 2</td>
                                <td>Mei 2024</td>
                                <td class="fw-bold">Rp 350.000</td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Pending</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalPreview" title="Lihat Bukti">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>13 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Ahmad+F&background=e6f9ee&color=198754&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ahmad Fadli</span>
                                    </div>
                                </td>
                                <td>XII IPA 3</td>
                                <td>Mei 2024</td>
                                <td class="fw-bold">Rp 400.000</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Disetujui</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" title="Sudah Diverifikasi" disabled><i class="bi bi-check-circle-fill text-secondary"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview Bukti Transfer -->
    <div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2"></i>Bukti Transfer - Budi Santoso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=BuktiTransferBudi" class="img-fluid rounded shadow border border-2 border-white p-3 bg-white" alt="Bukti Transfer Dummy">
                    <p class="mt-3 mb-0 text-muted">Transfer dari Bank BCA ke Bank BNI</p>
                    <p class="fw-bold text-dark">15 Mei 2024, 09:30 WIB - Rp 350.000</p>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Tolak (Reject)</button>
                    <button type="button" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Setujui (Approve)</button>
                </div>
            </div>
        </div>
    </div>
@endsection