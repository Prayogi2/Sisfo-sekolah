@extends('layouts.app')

@section('title', 'Hasil Kuis & Ranking Anak')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Hasil Kuis & Ranking</h1>
                <p class="text-muted mb-0">Pantau hasil pengerjaan kuis CBT anak Anda.</p>
            </div>
        </div>

        <!-- Ringkasan Performa Anak -->
        <div class="row mb-4">
            <div class="col-xl-6 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Rata-rata Nilai Kuis</div>
                                <div class="h4 mb-0 fw-bold text-dark">88.5</div>
                                <small class="text-success"><i class="bi bi-graph-up-arrow"></i> Stabil Baik</small>
                            </div>
                            <div class="p-3 rounded-3 bg-primary-soft" style="background-color: #e7f1ff;">
                                <i class="bi bi-award-fill fs-3 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Peringkat di Kelas</div>
                                <div class="h4 mb-0 fw-bold text-dark">Top 3 dari 30 Siswa</div>
                                <small class="text-muted">Kelas 6-A</small>
                            </div>
                            <div class="p-3 rounded-3" style="background-color: #fff8e6;">
                                <i class="bi bi-trophy-fill fs-3 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Riwayat Nilai Kuis -->
            <div class="col-lg-7 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Riwayat Nilai Kuis Anak</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Judul Kuis</th>
                                        <th class="text-center">Nilai</th>
                                        <th class="text-center">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>15 Mei 2024</td>
                                        <td>Matematika</td>
                                        <td>Kuis Aljabar Bab 3</td>
                                        <td class="text-center fw-bold text-primary">90</td>
                                        <td class="text-center"><span class="badge bg-success">A</span></td>
                                    </tr>
                                    <tr>
                                        <td>10 Mei 2024</td>
                                        <td>Bahasa Indonesia</td>
                                        <td>Teks Eksposisi</td>
                                        <td class="text-center fw-bold text-primary">85</td>
                                        <td class="text-center"><span class="badge bg-success">A</span></td>
                                    </tr>
                                    <tr>
                                        <td>05 Mei 2024</td>
                                        <td>Al-Qur'an Hadits</td>
                                        <td>Hukum Tajwid</td>
                                        <td class="text-center fw-bold text-warning">75</td>
                                        <td class="text-center"><span class="badge bg-primary">B</span></td>
                                    </tr>
                                    <tr>
                                        <td>28 Apr 2024</td>
                                        <td>IPA Terpadu</td>
                                        <td>Sistem Pencernaan</td>
                                        <td class="text-center fw-bold text-primary">95</td>
                                        <td class="text-center"><span class="badge bg-success">A</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard / Peringkat Kelas -->
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-trophy-fill me-2"></i>Peringkat Kelas 6-A</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <!-- Juara 1 -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning p-2 me-3 fs-6"><i class="bi bi-1-circle-fill"></i></span>
                                    <div>
                                        <span class="fw-bold text-dark">Siti Aminah</span><br>
                                        <small class="text-muted">Rata-rata: 92.5</small>
                                    </div>
                                </div>
                                <i class="bi bi-trophy-fill text-warning fs-5"></i>
                            </li>
                            <!-- Juara 2 -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-secondary p-2 me-3 fs-6"><i class="bi bi-2-circle-fill"></i></span>
                                    <div>
                                        <span class="fw-bold text-dark">Ahmad Fauzi</span><br>
                                        <small class="text-muted">Rata-rata: 90.0</small>
                                    </div>
                                </div>
                                <i class="bi bi-award-fill text-secondary fs-5"></i>
                            </li>
                            <!-- Anak Anda (Juara 3) - Disorot -->
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-primary-soft" style="background-color: #e7f1ff;">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-danger p-2 me-3 fs-6"><i class="bi bi-3-circle-fill"></i></span>
                                    <div>
                                        <span class="fw-bold text-primary">Budi Santoso (Anak Anda)</span><br>
                                        <small class="text-muted">Rata-rata: 88.5</small>
                                    </div>
                                </div>
                                <i class="bi bi-award-fill text-danger fs-5"></i>
                            </li>
                            <!-- Peringkat 4 -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light text-dark border p-2 me-3 fs-6">4</span>
                                    <div>
                                        <span class="fw-bold text-dark">Citra Lestari</span><br>
                                        <small class="text-muted">Rata-rata: 85.0</small>
                                    </div>
                                </div>
                            </li>
                            <!-- Peringkat 5 -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-light text-dark border p-2 me-3 fs-6">5</span>
                                    <div>
                                        <span class="fw-bold text-dark">Eka Wahyuni</span><br>
                                        <small class="text-muted">Rata-rata: 83.5</small>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div class="text-center p-3">
                            <small class="text-muted">*Menampilkan 5 peringkat teratas di kelas anak Anda.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection