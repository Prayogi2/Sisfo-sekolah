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
                        <h4 class="fw-bold mb-1">Selamat Datang, {{ $teacher?->name ?? 'Bpk/Ibu Guru' }}! 👋</h4>
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
                        <span class="text-muted small">Soal di Bank Soal</span>
                        <h3 class="fw-bold mb-0">{{ $stats['bank_soal'] }}</h3>
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
                        <span class="text-muted small">Kuis Sedang Dibuka</span>
                        <h3 class="fw-bold mb-0">{{ $stats['kuis_dibuka'] }} Kuis</h3>
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
                        <h3 class="fw-bold mb-0">{{ $stats['izin_pending'] }} Siswa</h3>
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
                        <span class="text-muted small">Kuis Sudah Dikerjakan</span>
                        <h3 class="fw-bold mb-0">{{ $stats['dikerjakan'] }} Siswa</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4 mb-4">
        <!-- Kuis Saya: buka saat jam pelajaran, tutup setelah selesai -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-journal-check text-primary me-2"></i> Kuis Saya</h5>
                    <span class="badge bg-primary-subtle text-primary fw-normal">{{ now()->translatedFormat('l, d M Y') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Judul Kuis</th>
                                    <th>Kelas</th>
                                    <th>Soal</th>
                                    <th>Durasi</th>
                                    <th>Akses Siswa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quizzes as $quiz)
                                    <tr>
                                        <td class="fw-semibold">{{ $quiz->title }}<br><small class="text-muted">{{ $quiz->subject->name }}</small></td>
                                        <td><span class="badge bg-secondary">{{ $quiz->classroom->name }}</span></td>
                                        <td>{{ $quiz->questions_count }}</td>
                                        <td>{{ $quiz->duration_minutes }} menit</td>
                                        <td>
                                            @if(! $quiz->is_published)
                                                <span class="badge bg-light text-dark border">Draft</span>
                                            @else
                                                <form action="{{ route('guru.kuis.toggle-open', $quiz) }}" method="POST">
                                                    @csrf
                                                    @if($quiz->is_open)
                                                        <button class="btn btn-sm btn-danger"><i class="bi bi-lock me-1"></i> Tutup</button>
                                                        <span class="badge bg-success ms-1">Dibuka</span>
                                                    @else
                                                        <button class="btn btn-sm btn-success"><i class="bi bi-unlock me-1"></i> Buka</button>
                                                        <span class="badge bg-secondary ms-1">Tertutup</span>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada kuis. Buat di menu <strong>Manajemen Bank Soal &amp; Kuis</strong>.
                                        </td>
                                    </tr>
                                @endforelse
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