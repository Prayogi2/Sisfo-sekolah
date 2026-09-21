@extends('layouts.app')

@section('title', 'Approval Izin & Sakit')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Approval Izin & Sakit Siswa</h4>
            <p class="text-muted mb-0">Verifikasi pengajuan surat izin dan sakit siswa kelas Anda.</p>
        </div>
    </div>

    <!-- Filter & Statistik Ringkas -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                <span class="text-muted small fw-semibold">Menunggu Persetujuan</span>
                <h3 class="fw-bold text-warning mb-0 mt-1">3 Pengajuan</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                <span class="text-muted small fw-semibold">Disetujui Hari Ini</span>
                <h3 class="fw-bold text-success mb-0 mt-1">5 Siswa</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                <span class="text-muted small fw-semibold">Ditolak</span>
                <h3 class="fw-bold text-danger mb-0 mt-1">1 Pengajuan</h3>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Pengajuan Izin -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Kategori</th>
                            <th>Tanggal Izin</th>
                            <th>Alasan / Keterangan</th>
                            <th>Bukti File</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><strong>Ahmad Rayhan</strong></td>
                            <td>Kelas 4A</td>
                            <td><span class="badge bg-info text-dark">Sakit</span></td>
                            <td>21 Sep 2026</td>
                            <td>Demam dan flu tinggi</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> Surat.pdf</a>
                            </td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg"></i> Setujui</button>
                                <button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><strong>Siti Aisyah</strong></td>
                            <td>Kelas 4A</td>
                            <td><span class="badge bg-primary">Izin (Acara)</span></td>
                            <td>22 Sep 2026</td>
                            <td>Acara keluarga di luar kota</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-image"></i> Surat.jpg</a>
                            </td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg"></i> Setujui</button>
                                <button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection