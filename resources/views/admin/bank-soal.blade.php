@extends('layouts.app')

@section('title', 'Kuis Guru - Bank Soal')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Kuis Guru Interaktif</h1>
                <p class="text-muted mb-0">Buat kuis menarik dengan batasan waktu dan media visual.</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBuatSoal">
                <i class="bi bi-plus-circle-fill me-1"></i> Buat Soal Baru
            </button>
        </div>

        <!-- Pengaturan Kuis Aktif -->
        <div class="card shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-3 mb-md-0">
                        <h5 class="fw-bold text-dark mb-1">Kuis Ulangan Harian Akidah Akhlak</h5>
                        <small class="text-muted">Kelas VI • 10 Soal • Durasi 30 Menit</small>
                    </div>
                    <div>
                        <span class="badge bg-success-soft text-success p-2 me-2" style="background-color: #e6f9ee;">Status: Aktif</span>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Lihat Hasil</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Soal -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Bank Soal</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">No</th>
                                <th>Teks Soal & Media</th>
                                <th>Opsi Jawaban</th>
                                <th>Timer</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>
                                    <div class="fw-semibold text-dark">Rukun Iman yang pertama adalah?</div>
                                    <small class="text-muted"><i class="bi bi-image me-1"></i> Tidak ada media</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <span class="badge bg-danger p-2">A</span>
                                        <span class="badge bg-primary p-2">B</span>
                                        <span class="badge bg-warning text-dark p-2">C</span>
                                        <span class="badge bg-success p-2">D</span>
                                    </div>
                                    <small class="text-success mt-1 d-block"><i class="bi bi-check-circle"></i> Kunci: B</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">20 Detik</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalBuatSoal"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>
                                    <div class="fw-semibold text-dark">Berapa jumlah rakaat shalat Maghrib?</div>
                                    <small class="text-primary"><i class="bi bi-image-fill me-1"></i> Gambar: shalat.jpg</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <span class="badge bg-danger p-2">3</span>
                                        <span class="badge bg-primary p-2">4</span>
                                        <span class="badge bg-warning text-dark p-2">2</span>
                                        <span class="badge bg-success p-2">5</span>
                                    </div>
                                    <small class="text-success mt-1 d-block"><i class="bi bi-check-circle"></i> Kunci: A</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">15 Detik</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalBuatSoal"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button class="btn btn-sm btn-light"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Buat/Edit Soal (Kahoot Style) -->
    <div class="modal fade" id="modalBuatSoal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-patch-question me-2"></i>Buat Soal Interaktif</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Timer & Media -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-stopwatch-fill fs-4 text-danger"></i>
                            <select class="form-select form-select-sm w-auto">
                                <option>10 Detik</option>
                                <option selected>20 Detik</option>
                                <option>30 Detik</option>
                                <option>60 Detik</option>
                            </select>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-image me-1"></i> Upload Gambar</button>
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-youtube me-1"></i> Upload Video</button>
                        </div>
                    </div>

                    <!-- Teks Soal -->
                    <div class="mb-4">
                        <textarea class="form-control form-control-lg text-center fw-bold" rows="2" style="font-size: 1.5rem;" placeholder="Tulis pertanyaan di sini..."></textarea>
                    </div>

                    <!-- Opsi Jawaban (Kahoot Colors) -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text text-white fw-bold border-0 rounded-start" style="background-color: #d63031; width: 50px; color: #fff !important;">A</span>
                                <input type="text" class="form-control border-0 shadow-sm" placeholder="Opsi Jawaban A">
                                <span class="input-group-text bg-white border-0 rounded-end"><input type="radio" name="kunci" class="form-check-input"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text text-white fw-bold border-0 rounded-start" style="background-color: #0984e3; width: 50px; color: #fff !important;">B</span>
                                <input type="text" class="form-control border-0 shadow-sm" placeholder="Opsi Jawaban B">
                                <span class="input-group-text bg-white border-0 rounded-end"><input type="radio" name="kunci" class="form-check-input" checked></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text text-dark fw-bold border-0 rounded-start" style="background-color: #fdcb6e; width: 50px;">C</span>
                                <input type="text" class="form-control border-0 shadow-sm" placeholder="Opsi Jawaban C">
                                <span class="input-group-text bg-white border-0 rounded-end"><input type="radio" name="kunci" class="form-check-input"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text text-white fw-bold border-0 rounded-start" style="background-color: #00b894; width: 50px; color: #fff !important;">D</span>
                                <input type="text" class="form-control border-0 shadow-sm" placeholder="Opsi Jawaban D">
                                <span class="input-group-text bg-white border-0 rounded-end"><input type="radio" name="kunci" class="form-check-input"></span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Pengaturan Opsi Hasil -->
                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-toggles me-2"></i>Pengaturan Tampilan Hasil</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-check-label bg-light border p-3 rounded d-block" style="cursor: pointer;">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="opsiHasil" checked>
                                    <div>
                                        <span class="fw-bold text-dark">Tampilkan Nilai & Rangking di Akhir Kuis</span>
                                        <small class="text-muted d-block">Siswa hanya melihat hasil setelah semua soal selesai dikerjakan.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check-label bg-light border p-3 rounded d-block" style="cursor: pointer;">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="opsiHasil">
                                    <div>
                                        <span class="fw-bold text-dark">Tampilkan Nilai Setiap 1 Soal Selesai</span>
                                        <small class="text-muted d-block">Siswa langsung tahu benar/salah dan nilainya setiap habis 1 soal.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Soal</button>
                </div>
            </div>
        </div>
    </div>
@endsection