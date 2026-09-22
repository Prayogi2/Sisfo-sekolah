@extends('layouts.app')

@section('title', 'Dashboard Wali Murid')

@php
    $pendingBill = $bills->first(fn ($bill) => $bill->remainingAmount() > 0);
    $attendanceCount = $history->filter(fn ($day) => $day['attendance']?->status?->value === 'hadir')->count();
    $averageScore = $attempts->isNotEmpty() ? round($attempts->avg('score'), 2) : 0;
@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="h3 mb-1 fw-bold">{{ $student ? 'Dashboard '.$student->name : 'Dashboard Wali Murid' }}</h1>
                <p class="text-muted mb-0">{{ $student?->classroom?->name ?? 'Belum ada anak yang terhubung' }}</p>
            </div>
            @if($student)<a href="{{ route('wali.prestasi-pelanggaran') }}" class="btn btn-outline-primary"><i class="bi bi-award me-1"></i>Prestasi & Pelanggaran</a>@endif
        </div>

        @if(!$student)
            <div class="alert alert-info">Akun ini belum memiliki data anak. Hubungkan siswa terlebih dahulu untuk melihat ringkasan akademik.</div>
        @else
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Kehadiran 30 hari</small><h3 class="mt-2 mb-0 text-success">{{ $attendanceCount }}</h3></div></div></div>
                <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Rata-rata kuis</small><h3 class="mt-2 mb-0 text-primary">{{ $averageScore }}%</h3></div></div></div>
                <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">Sisa tagihan SPP</small><h3 class="mt-2 mb-0">Rp {{ number_format($pendingBill?->remainingAmount() ?? 0, 0, ',', '.') }}</h3></div></div></div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-header bg-white d-flex justify-content-between"><h6 class="mb-0 fw-bold text-primary">Hasil kuis terbaru</h6><a href="{{ route('wali.kuis') }}">Lihat semua</a></div><div class="list-group list-group-flush">
                    @forelse($attempts->take(5) as $attempt)<div class="list-group-item d-flex justify-content-between"><span>{{ $attempt->quiz?->title ?? 'Kuis' }}<small class="d-block text-muted">{{ $attempt->quiz?->subject?->name }}</small></span><strong>{{ $attempt->score }}%</strong></div>@empty<div class="list-group-item text-muted">Belum ada hasil kuis.</div>@endforelse
                </div></div></div>
                <div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary">Status SPP</h6></div><div class="card-body">
                    @forelse($bills->take(3) as $bill)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $bill->period->translatedFormat('F Y') }}</span><span>Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</span></div>@empty<p class="text-muted mb-0">Belum ada tagihan.</p>@endforelse
                    <a href="{{ route('wali.spp') }}" class="btn btn-primary btn-sm w-100 mt-3">Lihat status SPP</a>
                </div></div></div>
            </div>
        @endif
    </div>
@endsection
