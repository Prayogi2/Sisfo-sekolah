@extends('layouts.app')

@section('title', 'Approval Izin & Sakit')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Approval Pengajuan Izin & Sakit</h1>
            <span class="badge bg-warning-soft text-warning p-2 shadow-sm" style="background-color: #fff8e6;">
                <i class="bi bi-clock-history me-1"></i> 2 Pengajuan Menunggu
            </span>
        </div>

        <!-- Tabel Pengajuan Izin -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Pengajuan dari Wali Murid</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Anak</th>
                                <th>Kelas</th>
                                <th>Alasan Izin</th>
                                <th>Lampiran</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>20 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=A+Fauzi&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ahmad Fauzi</span>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>Sakit Demam (2 Hari)</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalLampiran">
                                        <i class="bi bi-eye me-1"></i> Lihat Surat
                                    </button>
                                </td>
                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Pending</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" title="Tolak (Jadi Alpa)"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>19 Mei 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=S+Aminah&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Siti Aminah</span>
                                    </div>
                                </td>
                                <td>V</td>
                                <td>Acara Keluarga (1 Hari)</td>
                                <td class="text-center">
                                    <span class="text-muted">Tanpa Lampiran</span>
                                </td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Disetujui</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" disabled><i class="bi bi-check-circle text-secondary"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Viewer Lampiran -->
    <div class="modal fade" id="modalLampiran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-medical me-2"></i>Surat Keterangan Dokter</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <p class="text-muted small mb-2">Diajukan oleh: Ahmad Fauzi (Kelas VI) - 20 Mei 2024</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=SuratDokterFauzi" class="img-fluid rounded shadow border border-2 border-white p-3 bg-white" alt="Surat Dokter">
                </div>
                <div class="modal-footer bg-white d-flex justify-content-between">
                    <div class="text-start">
                        <small class="text-danger d-block"><i class="bi bi-exclamation-triangle-fill"></i> Jika ditolak, siswa akan dicatat <strong>Alpa</strong>.</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Tolak (Alpa)</button>
                        <button type="button" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Setujui Izin</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection