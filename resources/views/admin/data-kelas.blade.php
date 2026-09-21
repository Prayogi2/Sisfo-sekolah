@extends('layouts.app')

@section('title', 'Pembagian Kelas')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Pembagian Kelas</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kelas Baru
            </button>
        </div>

        <!-- Grid Card Kelas -->
        <div class="row">
            <!-- Card Kelas 1 -->
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-primary mb-0">Kelas 6-A</h5>
                            <span class="badge bg-primary-soft text-primary">Kapasitas: 30</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=Ustadz+Ali&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <small class="text-muted d-block">Wali Kelas</small>
                                <span class="fw-semibold text-dark">Ustadz Ali, S.Pd</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Terisi: 28 Siswa</span>
                                <span class="fw-bold text-success">93%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 93%;" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100"></div>
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
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-success mb-0">Kelas 5-B</h5>
                            <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Kapasitas: 30</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=Ustadzah+Siti&background=e6f9ee&color=198754&bold=true" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <small class="text-muted d-block">Wali Kelas</small>
                                <span class="fw-semibold text-dark">Ustadzah Siti, S.Pd</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Terisi: 30 Siswa</span>
                                <span class="fw-bold text-danger">Penuh</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
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
                        <input type="text" class="form-control" placeholder="Contoh: Kelas 6-C">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Wali Kelas Penanggung Jawab</label>
                        <select class="form-select">
                            <option>-- Pilih Guru --</option>
                            <option>Ustadz Ali, S.Pd</option>
                            <option>Ustadzah Siti, S.Pd</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kapasitas Siswa</label>
                        <input type="number" class="form-control" value="30">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atur Siswa (Plotting) -->
    <div class="modal fade" id="modalAturSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-people me-2"></i>Plotting Siswa - Kelas 6-A</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Pilih siswa dari kolom kiri, lalu klik tombol panah kanan untuk memasukkan ke kelas. Sebaliknya, gunakan panah kiri untuk mengeluarkan siswa.</p>
                    <div class="row">
                        <div class="col-md-5">
                            <label class="fw-semibold mb-2">Siswa Belum Memiliki Kelas (5)</label>
                            <select class="form-select" multiple style="height: 300px;">
                                <option>Ahmad Fauzi</option>
                                <option>Siti Aminah</option>
                                <option>Budi Santoso</option>
                                <option>Eka Wahyuni</option>
                                <option>Rani Puspita</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center align-items-center gap-3">
                            <button class="btn btn-outline-primary w-100"><i class="bi bi-arrow-right-circle fs-4"></i></button>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-arrow-left-circle fs-4"></i></button>
                        </div>
                        <div class="col-md-5">
                            <label class="fw-semibold mb-2">Anggota Kelas 6-A (28)</label>
                            <select class="form-select" multiple style="height: 300px;">
                                <option>Citra Lestari</option>
                                <option>Andi Pratama</option>
                                <option>Dewi Anggraini</option>
                                <option>Joko Widodo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button class="btn btn-primary">Simpan Pembagian</button>
                </div>
            </div>
        </div>
    </div>
@endsection