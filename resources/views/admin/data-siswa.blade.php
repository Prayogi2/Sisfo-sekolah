@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Data Siswa</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Siswa
            </button>
        </div>

        <!-- Filter & Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Cari Siswa</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="Nama atau NISN...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select class="form-select">
                            <option value="">Semua Kelas</option>
                            <option>X IPA 1</option>
                            <option>XI IPS 2</option>
                            <option>XII IPA 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Siswa Aktif (1,245 Siswa)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Kelamin</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>0098761234</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Budi+S&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35" alt="">
                                        <span class="fw-semibold text-dark">Budi Santoso</span>
                                    </div>
                                </td>
                                <td>X IPA 1</td>
                                <td>Laki-laki</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Aktif</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa" title="Detail"><i class="bi bi-eye text-primary"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditSiswa" title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>0098761235</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Siti+N&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35" alt="">
                                        <span class="fw-semibold text-dark">Siti Nurhaliza</span>
                                    </div>
                                </td>
                                <td>XI IPS 2</td>
                                <td>Perempuan</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Aktif</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa" title="Detail"><i class="bi bi-eye text-primary"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditSiswa" title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>0098761236</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Ahmad+F&background=ffeaea&color=dc3545&bold=true" class="rounded-circle me-2" width="35" height="35" alt="">
                                        <span class="fw-semibold text-dark">Ahmad Fadli</span>
                                    </div>
                                </td>
                                <td>XII IPA 3</td>
                                <td>Laki-laki</td>
                                <td><span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">Keluar</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa" title="Detail"><i class="bi bi-eye text-primary"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditSiswa" title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light btn-sm" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Siswa -->
    <div class="modal fade" id="modalTambahSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NISN</label>
                            <input type="text" class="form-control" placeholder="Masukkan NISN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NIS</label>
                            <input type="text" class="form-control" placeholder="Masukkan NIS">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" placeholder="Nama sesuai ijazah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select class="form-select">
                                <option>Laki-laki</option>
                                <option>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas</label>
                            <select class="form-select">
                                <option>X IPA 1</option>
                                <option>XI IPS 2</option>
                                <option>XII IPA 3</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary">Simpan Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Siswa -->
    <div class="modal fade" id="modalDetailSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-primary">Detail Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="https://ui-avatars.com/api/?name=Budi+S&size=100&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle mb-3 shadow-sm" width="100" height="100" alt="">
                    <h4 class="fw-bold text-dark">Budi Santoso</h4>
                    <p class="text-muted">NISN: 0098761234 | NIS: 1002456</p>
                    <span class="badge bg-primary-soft text-primary mb-3">Kelas X IPA 1</span>
                </div>
                <div class="modal-footer bg-light d-block">
                    <div class="row text-start">
                        <div class="col-6 mb-2"><small class="text-muted d-block">Jenis Kelamin</small><span class="fw-semibold">Laki-laki</span></div>
                        <div class="col-6 mb-2"><small class="text-muted d-block">Tanggal Lahir</small><span class="fw-semibold">12 Agustus 2008</span></div>
                        <div class="col-6 mb-2"><small class="text-muted d-block">No. Telepon</small><span class="fw-semibold">081234567890</span></div>
                        <div class="col-6 mb-2"><small class="text-muted d-block">Status</span><span class="badge bg-success">Aktif</span></div>
                        <div class="col-12"><small class="text-muted d-block">Alamat</small><span class="fw-semibold">Jl. Merdeka No. 123, Jakarta</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection