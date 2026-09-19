@extends('layouts.app')

@section('title', 'Prestasi & Pelanggaran')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Catatan Prestasi & Tata Tertib</h1>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-pills nav-fill bg-primary-soft p-2 rounded mb-4" id="kategoriTab" role="tablist" style="background-color: #e7f1ff;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="prestasi-tab" data-bs-toggle="pill" data-bs-target="#prestasi" type="button">
                    <i class="bi bi-trophy-fill me-1"></i> Prestasi Siswa
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="pelanggaran-tab" data-bs-toggle="pill" data-bs-target="#pelanggaran" type="button">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Pelanggaran Tata Tertib
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab Prestasi -->
            <div class="tab-pane fade show active" id="prestasi" role="tabpanel">
                <div class="row">
                    <!-- Form Input Prestasi -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm sticky-top" style="top: 80px;">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                                <h6 class="m-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Input Prestasi Baru</h6>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Siswa</label>
                                        <select class="form-select">
                                            <option>Ahmad Fauzi (VI)</option>
                                            <option>Siti Aminah (V)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Jenis Prestasi</label>
                                        <select class="form-select">
                                            <option>Akademik (Juara Olimpiade)</option>
                                            <option>Non-Akademik (Olahraga/Seni)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Lomba / Event</label>
                                        <input type="text" class="form-control" placeholder="Contoh: MTQ Tingkat Kabupaten">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Pencapaian</label>
                                        <input type="text" class="form-control" placeholder="Contoh: Juara 1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Benefit Diterima</label>
                                        <input type="text" class="form-control" placeholder="Contoh: Sertifikat + Uang Saku">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Simpan Prestasi</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Prestasi -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header py-3 bg-white">
                                <h6 class="m-0 fw-bold text-primary">Riwayat Prestasi Sekolah</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex align-items-center">
                                        <div class="bg-warning-soft p-3 rounded-3 me-3" style="background-color: #fff8e6;">
                                            <i class="bi bi-trophy-fill fs-4 text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-bold text-dark">Juara 1 Lomba Tahfidz</span>
                                            <p class="mb-0 text-muted small">Ahmad Fauzi (VI) • Tingkat Kabupaten • Benefit: Sertifikat & Uang Saku</p>
                                        </div>
                                        <button class="btn btn-sm btn-light"><i class="bi bi-trash text-danger"></i></button>
                                    </div>
                                    <div class="list-group-item d-flex align-items-center">
                                        <div class="bg-warning-soft p-3 rounded-3 me-3" style="background-color: #fff8e6;">
                                            <i class="bi bi-award-fill fs-4 text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-bold text-dark">Juara 2 Olimpiade Matematika</span>
                                            <p class="mb-0 text-muted small">Siti Aminah (V) • Tingkat Kecamatan • Benefit: Sertifikat</p>
                                        </div>
                                        <button class="btn btn-sm btn-light"><i class="bi bi-trash text-danger"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Pelanggaran -->
            <div class="tab-pane fade" id="pelanggaran" role="tabpanel">
                <div class="row">
                    <!-- Form Input Pelanggaran -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm sticky-top" style="top: 80px;">
                            <div class="card-header text-white bg-danger">
                                <h6 class="m-0 fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Catat Pelanggaran Baru</h6>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Siswa</label>
                                        <select class="form-select">
                                            <option>Budi Santoso (IV)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Kategori Pelanggaran</label>
                                        <select class="form-select">
                                            <option>Ringan</option>
                                            <option>Sedang</option>
                                            <option>Berat</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Penjabaran / Detail</label>
                                        <textarea class="form-control" rows="3" placeholder="Contoh: Terlambat masuk kelas 3x berturut-turut"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Dokumentasi Foto Bukti (Jika ada)</label>
                                        <input type="file" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100">Simpan Catatan</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Pelanggaran -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header py-3 bg-white">
                                <h6 class="m-0 fw-bold text-danger">Riwayat Pelanggaran Siswa</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Siswa</th>
                                                <th>Pelanggaran</th>
                                                <th>Kategori</th>
                                                <th>Bukti Foto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>18 Mei 2024</td>
                                                <td>Budi Santoso (IV)</td>
                                                <td>Tidak memakai dasi saat upacara</td>
                                                <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Ringan</span></td>
                                                <td class="text-muted">Tidak Ada</td>
                                            </tr>
                                            <tr>
                                                <td>15 Mei 2024</td>
                                                <td>Budi Santoso (IV)</td>
                                                <td>Bermain gadget saat jam pelajaran</td>
                                                <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Sedang</span></td>
                                                <td>
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=Bukti" width="40" height="40" class="rounded border" alt="Bukti">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection