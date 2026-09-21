@extends('layouts.app')

@section('title', 'Bank Soal CBT')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Bank Soal CBT (Ulangan Harian)</h1>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSoal">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Soal CBT
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white d-flex justify-content-between">
            <h6 class="m-0 fw-bold text-primary">Daftar Soal: Akidah Akhlak (Kelas VI)</h6>
            <span class="badge bg-primary-soft text-primary">10 Soal</span>
        </div>
        <div class="card-body p-4">
            
            <!-- Soal 1 -->
            <div class="bg-light p-4 rounded mb-4 border-start border-primary border-4">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="fw-bold text-dark">1. Rukun Iman yang pertama adalah?</h5>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-danger p-2"><i class="bi bi-stopwatch"></i> 20 Detik</span>
                        <span class="badge bg-info p-2"><i class="bi bi-image"></i> Media</span>
                    </div>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-danger text-white me-2 p-2">A</span> Iman kepada Malaikat
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border border-success border-2">
                        <span class="badge bg-primary text-white me-2 p-2">B</span> Iman kepada Allah
                        <i class="bi bi-check-circle-fill text-success ms-auto fs-5"></i> <small class="text-success fw-bold ms-1">Kunci</small>
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-warning text-dark me-2 p-2">C</span> Iman kepada Rasul
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-success text-white me-2 p-2">D</span> Iman kepada Qada & Qadar
                    </div>
                </div>
                
                <div class="text-end mt-3">
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalSoal"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</button>
                </div>
            </div>

            <!-- Soal 2 -->
            <div class="bg-light p-4 rounded border-start border-primary border-4">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="fw-bold text-dark">2. Berapa jumlah rakaat shalat Maghrib?</h5>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-danger p-2"><i class="bi bi-stopwatch"></i> 15 Detik</span>
                        <span class="badge bg-secondary p-2"><i class="bi bi-image-off"></i> Tanpa Media</span>
                    </div>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border border-success border-2">
                        <span class="badge bg-danger text-white me-2 p-2">A</span> 3 Rakaat
                        <i class="bi bi-check-circle-fill text-success ms-auto fs-5"></i> <small class="text-success fw-bold ms-1">Kunci</small>
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-primary text-white me-2 p-2">B</span> 4 Rakaat
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-warning text-dark me-2 p-2">C</span> 2 Rakaat
                    </div>
                    <div class="col-md-6 d-flex align-items-center bg-white p-2 rounded border">
                        <span class="badge bg-success text-white me-2 p-2">D</span> 5 Rakaat
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Soal CBT -->
<div class="modal fade" id="modalSoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-patch-question me-2"></i>Form Soal CBT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <select class="form-select form-select-sm w-auto">
                        <option>10 Detik</option>
                        <option selected>20 Detik</option>
                        <option>30 Detik</option>
                    </select>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-image me-1"></i> Upload Gambar</button>
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-youtube me-1"></i> Upload Video</button>
                    </div>
                </div>

                <textarea class="form-control form-control-lg text-center fw-bold mb-3" rows="2" placeholder="Teks Pertanyaan..."></textarea>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text text-white fw-bold" style="background-color: #d63031; width: 40px;">A</span>
                            <input type="text" class="form-control" placeholder="Opsi A">
                            <div class="input-group-text"><input type="radio" name="kunci"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text text-white fw-bold" style="background-color: #0984e3; width: 40px;">B</span>
                            <input type="text" class="form-control" placeholder="Opsi B">
                            <div class="input-group-text"><input type="radio" name="kunci" checked></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text text-dark fw-bold" style="background-color: #fdcb6e; width: 40px;">C</span>
                            <input type="text" class="form-control" placeholder="Opsi C">
                            <div class="input-group-text"><input type="radio" name="kunci"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text text-white fw-bold" style="background-color: #00b894; width: 40px;">D</span>
                            <input type="text" class="form-control" placeholder="Opsi D">
                            <div class="input-group-text"><input type="radio" name="kunci"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Soal</button>
            </div>
        </div>
    </div>
</div>
@endsection