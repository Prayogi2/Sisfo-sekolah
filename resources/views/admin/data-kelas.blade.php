@extends('layouts.app')

@section('title', 'Data Kelas')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Data Kelas & Pembagian</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kelas Baru
            </button>
        </div>

        <!-- Grid Daftar Kelas -->
        <div class="row">
            <!-- Card Kelas 1 -->
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-primary mb-0">X IPA 1</h5>
                            <span class="badge bg-primary-soft text-primary">36 Siswa</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=Pak+Budi&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                            <div>
                                <small class="text-muted d-block">Wali Kelas</small>
                                <span class="fw-semibold text-dark">Budi Santoso, S.Pd</span>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalAturSiswa"><i class="bi bi-people"></i> Atur Siswa</button>
                            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Kelas 2 -->
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-success mb-0">XI IPS 2</h5>
                            <span class="badge bg-success-soft text-success" style="background-color:#e6f9ee;">30 Siswa</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=Bu+Sinta&background=e6f9ee&color=198754&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                            <div>
                                <small class="text-muted d-block">Wali Kelas</small>
                                <span class="fw-semibold text-dark">Sinta Dewi, S.Pd</span>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalAturSiswa"><i class="bi bi-people"></i> Atur Siswa</button>
                            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Kelas 3 -->
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-warning mb-0">XII IPA 3</h5>
                            <span class="badge bg-warning-soft text-warning" style="background-color:#fff8e6;">32 Siswa</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=Pak+Andi&background=fff8e6&color=f59e0b&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                            <div>
                                <small class="text-muted d-block">Wali Kelas</small>
                                <span class="fw-semibold text-dark">Andi Pratama, M.Pd</span>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalAturSiswa"><i class="bi bi-people"></i> Atur Siswa</button>
                            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kelas -->
    <div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kelas</label>
                        <input type="text" class="form-control" placeholder="Contoh: X IPA 2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Wali Kelas</label>
                        <select class="form-select">
                            <option>-- Pilih Guru --</option>
                            <option>Budi Santoso, S.Pd</option>
                            <option>Sinta Dewi, S.Pd</option>
                            <option>Andi Pratama, M.Pd</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tingkat</label>
                        <select class="form-select">
                            <option>X (Sepuluh)</option>
                            <option>XI (Sebelas)</option>
                            <option>XII (Dua Belas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atur Pembagian Siswa -->
    <div class="modal fade" id="modalAturSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-people me-2"></i>Atur Pembagian Siswa - X IPA 1</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Pindahkan siswa dari kolom kiri (Belum punya kelas) ke kolom kanan (Kelas X IPA 1).</p>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="fw-semibold mb-2">Siswa Tanpa Kelas (5)</label>
                            <select class="form-select" multiple style="height: 250px;">
                                <option>Ahmad Fadli</option>
                                <option>Dewi Persik</option>
                                <option>Eka Wahyuni</option>
                                <option>Joko Widodo</option>
                                <option>Rani Puspita</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold mb-2">Anggota X IPA 1 (36)</label>
                            <select class="form-select" multiple style="height: 250px;">
                                <option>Budi Santoso</option>
                                <option>Citra Lestari</option>
                                <option>Andi Pratama</option>
                                <option>Siti Nurhaliza</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-arrow-right"></i> Masukkan ke Kelas</button>
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-left"></i> Keluarkan dari Kelas</button>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-primary">Simpan Pembagian</button>
                </div>
            </div>
        </div>
    </div>
@endsection