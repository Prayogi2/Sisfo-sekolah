@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@php
    $pendingBill = $bills->first(fn ($bill) => $bill->remainingAmount() > 0);
    $attendanceLabels = [
        'hadir' => ['Hadir', 'success'],
        'telat' => ['Telat', 'warning'],
        'izin' => ['Izin', 'info'],
        'alpa' => ['Alpa', 'danger'],
    ];
@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
            <div>
                <h1 class="h3 mb-1 fw-bold">{{ $student ? 'Selamat datang, '.$student->name : 'Dashboard Siswa' }}</h1>
                <p class="text-muted mb-0">{{ $student?->classroom?->name ?? 'Pilih anak untuk melihat data akademik' }} · {{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            @if($student)<a href="{{ route('siswa.kartu-digital') }}" class="btn btn-outline-primary"><i class="bi bi-qr-code me-1"></i>Kartu Digital</a>@endif
        </div>

        <x-page-guide>Ringkasan absensi, tagihan, dan kuis hari ini. Kerjakan kuis lewat menu Kuis setelah absen pagi.</x-page-guide>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Absensi hari ini</small><h4 class="mt-2 mb-0 text-{{ $today?->status?->value === 'hadir' ? 'success' : 'warning' }}">{{ $today?->status?->value ? ucfirst($today->status->value) : 'Belum tercatat' }}</h4></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Tagihan berjalan</small><h4 class="mt-2 mb-0">Rp {{ number_format($pendingBill?->remainingAmount() ?? 0, 0, ',', '.') }}</h4></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Kuis tersedia</small><h4 class="mt-2 mb-0 text-primary">{{ $quizzes->count() }} kuis</h4></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><h6 class="mb-0 fw-bold text-primary">Kuis CBT</h6><a href="{{ route('siswa.kuis') }}" class="small">Lihat semua</a></div>
                    <div class="list-group list-group-flush">
                        @forelse($quizzes as $quiz)
                            @php($attempt = $quiz->attempts->first())
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                <div><div class="fw-semibold">{{ $quiz->title }}</div><small class="text-muted">{{ $quiz->subject?->name ?? 'Tanpa mapel' }} · {{ $quiz->duration_minutes }} menit</small></div>
                                @if($attempt?->status === 'submitted') <span class="badge bg-success">{{ $attempt->score }}%</span> @else <a href="{{ route('siswa.kuis.start', $quiz) }}" class="btn btn-sm btn-primary">Kerjakan</a> @endif
                            </div>
                        @empty
                            <div class="list-group-item text-muted">Belum ada kuis yang tersedia.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary">Ringkasan absensi bulan ini</h6></div><div class="card-body"><div class="row text-center g-2">
                    @foreach($attendanceLabels as $status => [$label, $color])
                        <div class="col-6 col-md-3"><div class="h4 fw-bold text-{{ $color }}">{{ $monthAttendances->filter(fn ($attendance) => $attendance->status?->value === $status)->count() }}</div><small class="text-muted">{{ $label }}</small></div>
                    @endforeach
                </div></div></div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary">SPP</h6></div><div class="card-body">
                    @forelse($bills->take(3) as $bill)
                        <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $bill->period->translatedFormat('F Y') }}</span><span class="fw-semibold">Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</span></div>
                    @empty <p class="text-muted mb-0">Belum ada tagihan.</p> @endforelse
                    <a href="{{ route('siswa.spp') }}" class="btn btn-primary btn-sm w-100 mt-3">Buka pembayaran SPP</a>
                </div></div>
            </div>
        </div>
    </div>
@endsection
