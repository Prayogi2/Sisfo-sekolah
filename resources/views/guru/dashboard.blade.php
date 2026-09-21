@extends('layouts.app')

@section('title', 'Dashboard Guru')

@section('content')
<div class="container-fluid">
    <!-- Header Welcome -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold mb-1">Selamat Datang, Bpk/Ibu Guru! 👋</h4>
                        <p class="mb-0 opacity-75">Pantau jadwal mengajar, absensi siswa, dan evaluasi kuis harian dalam satu antarmuka.</p>
                    </div>
                    <div class="d-none d-md-block fs-1 opacity-50">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards (Hampir sama dengan Admin, tapi fokus data Guru) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 fs-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Siswa Diajar</span>
                        <h3 class="fw-bold mb-0">128</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3 fs-3">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Jadwal Hari Ini</span>
                        <h3 class="fw-bold mb-0">3 Kelas</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 me-3 fs-3">
                        <i class="bi bi-envelope-open-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Izin Perlu Approval</span>
                        <h3 class="fw-bold mb-0">5 Siswa</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 me-3 fs-3">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Kuis Aktif</span>
                        <h3 class="fw-bold mb-0">2 Kuis</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4 mb-4">
        <!-- Jadwal Mengajar Hari Ini -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i> Jadwal Mengajar Hari Ini</h5>
                    <span class="badge bg-primary-subtle text-primary fw-normal">Senin, 21 Sep 2026</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jam</th>
                                    <th>Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Ruangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">07.30 - 09.00</td>
                                    <td><span class="badge bg-secondary">Kelas V-A</span></td>
                                    <td>Matematika</td>
                                    <td>R. 102</td>
                                    <td><span class="badge bg-success">Selesai</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">09.15 - 10.45</td>
                                    <td><span class="badge bg-secondary">Kelas VI-B</span></td>
                                    <td>IPA Terpadu</td>
                                    <td>Lab IPA</td>
                                    <td><span class="badge bg-warning text-dark">Sedang Berlangsung</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">11.00 - 12.30</td>
                                    <td><span class="badge bg-secondary">Kelas IV-A</span></td>
                                    <td>Matematika</td>
                                    <td>R. 101</td>
                                    <td><span class="badge bg-light text-dark border">Mendatang</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Akses Cepat / Quick Actions -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-2"></i> Aksi Cepat Guru</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('guru.approval-izin') }}" class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-envelope-paper me-2 fs-5"></i>
                                <span class="fw-semibold">Approval Izin & Sakit</span>
                            </div>
                            <span class="badge bg-danger rounded-pill">5</span>
                        </a>

                        <a href="{{ route('guru.bank-soal') }}" class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-journal-plus me-2 fs-5"></i>
                                <span class="fw-semibold">Buat Soal / Kuis Baru</span>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="{{ route('guru.laporan-nilai') }}" class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-pencil-square me-2 fs-5"></i>
                                <span class="fw-semibold">Input / Rekap Nilai</span>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection