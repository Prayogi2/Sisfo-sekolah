@extends('layouts.app')

@section('title', 'Data Guru & Wali Kelas')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Data Guru & Wali Kelas</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Guru Baru
            </button>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-primary border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Guru</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">32 Orang</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Wali Kelas</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">6 Orang</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Guru Aktif</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">30 Orang</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Tabel -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Daftar Tenaga Pendidik</h6>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Cari nama/NIP..." style="width: 200px;">
                    <select class="form-select form-select-sm" style="width: 150px;">
                        <option>Semua Status</option>
                        <option>Wali Kelas</option>
                        <option>Guru Mapel</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIP / NUPTK</th>
                                <th>Nama Lengkap</th>
                                <th>Mata Pelajaran</th>
                                <th>Status</th>
                                <th>Kontak</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1234567890</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Ustadz+Ali&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ustadz Ali, S.Pd</span>
                                    </div>
                                </td>
                                <td>Matematika</td>
                                <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Wali Kelas 6-A</span></td>
                                <td>081234567890</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" title="Detail"><i class="bi bi-eye text-primary"></i></button>
                                    <button class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>1234567891</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Ustadzah+Fati&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                        <span class="fw-semibold text-dark">Ustadzah Fati, S.Pd.I</span>
                                    </div>
                                </td>
                                <td>Al-Qur'an Hadits</td>
                                <td><span class="badge bg-primary-soft text-primary">Guru Mapel</span></td>
                                <td>081234567891</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" title="Detail"><i class="bi bi-eye text-primary"></i></button>
                                    <button class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Guru -->
    <div class="modal fade" id="modalTambahGuru" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Tambah Guru Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">NIP / NUPTK</label><input type="text" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Nama Lengkap (Berserta Gelar)</label><input type="text" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Mata Pelajaran</label><input type="text" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Status</label><select class="form-select"><option>Guru Mapel</option><option>Wali Kelas</option></select></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">No. WhatsApp</label><input type="text" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Data Guru</button>
                </div>
            </div>
        </div>
    </div>
@endsection