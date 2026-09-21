@extends('layouts.app')

@section('title', 'Laporan & Rekap Nilai Siswa')

@section('content')
<div class="container-fluid">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Laporan & Rekap Nilai Siswa</h4>
            <p class="text-muted mb-0">Rekapitulasi nilai akademis, kuis, UTS, UAS, dan status kelulusan siswa per kelas.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInputNilai">
                <i class="bi bi-pencil-square me-1"></i> Input / Update Nilai
            </button>
            <button class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
            </button>
            <button class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
            </button>
        </div>
    </div>

    <!-- Alert Sukses -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Ringkasan Statistik Nilai -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 fs-4">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Siswa Dinilai</span>
                        <h4 class="fw-bold mb-0">128</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3 fs-4">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Rata-Rata Nilai Kelas</span>
                        <h4 class="fw-bold mb-0">86.5</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 me-3 fs-4">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Nilai Tertinggi</span>
                        <h4 class="fw-bold mb-0">98.0</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 me-3 fs-4">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Perlu Remedial</span>
                        <h4 class="fw-bold mb-0">3 Siswa</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Pencarian -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Tahun Akademik / Semester</label>
                    <select class="form-select" name="semester">
                        <option value="2026-1">2026/2027 - Ganjil</option>
                        <option value="2025-2">2025/2026 - Genap</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select class="form-select" name="kelas">
                        <option value="V-A">Kelas V-A</option>
                        <option value="VI-B">Kelas VI-B</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <select class="form-select" name="mapel">
                        <option value="Matematika">Matematika</option>
                        <option value="IPA">IPA Terpadu</option>
                        <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Nilai -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-table text-primary me-2"></i> Data Nilai Siswa: Kelas V-A (Matematika)</h6>
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Cari nama siswa...">
                <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">Tugas (20%)</th>
                            <th class="text-center">Kuis (20%)</th>
                            <th class="text-center">UTS (30%)</th>
                            <th class="text-center">UAS (30%)</th>
                            <th class="text-center">Nilai Akhir</th>
                            <th class="text-center">Grade</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>0012345678</td>
                            <td class="fw-semibold">Ahmad Fauzi</td>
                            <td class="text-center">85</td>
                            <td class="text-center">90</td>
                            <td class="text-center">80</td>
                            <td class="text-center">88</td>
                            <td class="text-center fw-bold text-primary">85.1</td>
                            <td class="text-center"><span class="badge bg-primary">A</span></td>
                            <td class="text-center"><span class="badge bg-success">Lulus</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalInputNilai"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>0012345679</td>
                            <td class="fw-semibold">Siti Nurhaliza</td>
                            <td class="text-center">90</td>
                            <td class="text-center">95</td>
                            <td class="text-center">88</td>
                            <td class="text-center">92</td>
                            <td class="text-center fw-bold text-primary">91.1</td>
                            <td class="text-center"><span class="badge bg-primary">A+</span></td>
                            <td class="text-center"><span class="badge bg-success">Lulus</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalInputNilai"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>0012345680</td>
                            <td class="fw-semibold">Budi Santoso</td>
                            <td class="text-center">60</td>
                            <td class="text-center">65</td>
                            <td class="text-center">55</td>
                            <td class="text-center">58</td>
                            <td class="text-center fw-bold text-danger">59.0</td>
                            <td class="text-center"><span class="badge bg-danger">D</span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark">Remedial</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalInputNilai"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Input / Edit Nilai -->
<div class="modal fade" id="modalInputNilai" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Input / Edit Nilai Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('guru.laporan-nilai.simpan') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Siswa</label>
                            <select class="form-select" name="siswa_id" required>
                                <option value="">-- Pilih Siswa --</option>
                                <option value="1">Ahmad Fauzi (V-A)</option>
                                <option value="2">Siti Nurhaliza (V-A)</option>
                                <option value="3">Budi Santoso (V-A)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mata Pelajaran</label>
                            <select class="form-select" name="mapel" required>
                                <option value="Matematika">Matematika</option>
                                <option value="IPA">IPA Terpadu</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai Tugas (20%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="tugas" placeholder="0-100" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai Kuis (20%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="kuis" placeholder="0-100" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai UTS (30%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="uts" placeholder="0-100" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai UAS (30%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="uas" placeholder="0-100" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Nilai</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection