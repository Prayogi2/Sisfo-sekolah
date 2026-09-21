@extends('layouts.app')

@section('title', 'Manajemen Bank Soal & Kuis')

@section('content')
<div class="container-fluid">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Bank Soal & Kuis</h4>
            <p class="text-muted mb-0">Kelola daftar kuis online, bank soal, dan durasi ujian untuk siswa.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSoal">
            <i class="bi bi-plus-lg me-1"></i> Buat Soal / Kuis Baru
        </button>
    </div>

    <!-- Alert Sukses -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tabel Daftar Bank Soal -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Daftar Kuis & Soal Aktif</h6>
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Cari soal/topik...">
                <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Judul Kuis / Topik</th>
                            <th>Target Kelas</th>
                            <th>Jumlah Soal</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Matematika</td>
                            <td class="fw-semibold">Kuis Harian 1 - Operasi Hitung</td>
                            <td><span class="badge bg-secondary">Kelas V-A</span></td>
                            <td>10 Soal</td>
                            <td>30 Menit</td>
                            <td><span class="badge bg-success">Aktif</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>IPA Terpadu</td>
                            <td class="fw-semibold">Evaluasi Bab 2 - Ekosistem</td>
                            <td><span class="badge bg-secondary">Kelas VI-B</span></td>
                            <td>15 Soal</td>
                            <td>45 Menit</td>
                            <td><span class="badge bg-success">Aktif</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Input Soal Baru -->
<div class="modal fade" id="modalTambahSoal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Buat Soal / Kuis Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('guru.bank-soal.simpan') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mata Pelajaran</label>
                            <select class="form-select" name="mapel" required>
                                <option value="">-- Pilih Mapel --</option>
                                <option value="Matematika">Matematika</option>
                                <option value="IPA Terpadu">IPA Terpadu</option>
                                <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Kelas</label>
                            <select class="form-select" name="kelas" required>
                                <option value="V-A">Kelas V-A</option>
                                <option value="VI-B">Kelas VI-B</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Kuis / Topik Soal</label>
                        <input type="text" class="form-control" name="judul" placeholder="Contoh: Kuis Harian Perkalian" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Durasi (Menit)</label>
                            <input type="number" class="form-control" name="durasi" placeholder="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jumlah Soal</label>
                            <input type="number" class="form-control" name="jumlah_soal" placeholder="10" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pertanyaan Soal</label>
                        <textarea class="form-control" name="pertanyaan" rows="3" placeholder="Tuliskan pertanyaan di sini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection