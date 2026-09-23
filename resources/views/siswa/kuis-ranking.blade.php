@extends('layouts.app')

@section('title', 'Kuis & Ranking')

@section('content')
<div class="container-fluid"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1 fw-bold">Kuis & Ranking</h1><p class="text-muted mb-0">Kuis yang tersedia untuk kelas {{ $student->classroom?->name ?? '-' }}.</p></div></div>
    <x-page-guide>Kuis baru bisa dikerjakan setelah absen QR pagi ini dan guru membuka sesinya. Kuis dikerjakan serentak (Kahoot) — masuk begitu guru menekan Mulai, lalu jawab di layar ini mengikuti soal yang tampil.</x-page-guide>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @unless($hasCheckedIn)
        <div class="alert alert-warning d-flex align-items-start gap-2 shadow-sm border-0">
            <i class="bi bi-qr-code-scan fs-4"></i>
            <div>
                <strong>Belum absen hari ini.</strong>
                <div class="small">Kuis baru bisa dikerjakan setelah scan QR presensi masuk di sekolah.</div>
            </div>
        </div>
    @endunless
    <div class="row g-4">@forelse($quizzes as $quiz)@php $attempt = $quiz->attempts->first(); @endphp<div class="col-lg-6"><div class="card shadow-sm h-100 border-start border-primary border-4"><div class="card-body"><div class="d-flex justify-content-between"><span class="badge bg-primary">{{ $quiz->subject->name }}</span><span class="badge {{ $attempt?->status === 'submitted' ? 'bg-success' : ($quiz->isAvailable() ? 'bg-info' : 'bg-secondary') }}">{{ $attempt?->status === 'submitted' ? 'Selesai' : ($quiz->isAvailable() ? 'Dibuka' : 'Belum dibuka') }}</span></div><h5 class="fw-bold mt-3">{{ $quiz->title }}</h5><p class="text-muted small">{{ $quiz->questions()->count() }} soal · {{ $quiz->duration_minutes }} menit</p>@if($quiz->description)<p class="small">{{ $quiz->description }}</p>@endif
                        @if($attempt?->status === 'submitted')<div class="alert alert-success py-2">Nilai: <strong>{{ $attempt->score }}</strong></div><a href="{{ route('siswa.hasil-kuis') }}" class="btn btn-outline-primary w-100">Lihat Riwayat Hasil</a>@elseif(! $quiz->is_open || ! $quiz->isWithinSchedule())
                            <button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-hourglass-split me-1"></i> Belum Dibuka Guru</button>
                        @elseif(! $hasCheckedIn)
                            <button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-lock me-1"></i> Absen Dulu</button>
                        @elseif($quiz->isAvailable())<a href="{{ route($quiz->isLive() ? 'siswa.kuis.live' : 'siswa.kuis.start', $quiz) }}" class="btn btn-primary w-100">{{ $quiz->isLive() ? 'Masuk Sesi Kahoot' : ($attempt ? 'Lanjutkan Kuis' : 'Mulai Kuis') }}</a>@else<button class="btn btn-outline-secondary w-100" disabled>Belum Dimulai</button>@endif</div></div></div>@empty<div class="col-12"><div class="card shadow-sm"><div class="card-body text-center text-muted py-5">Belum ada kuis yang dipublikasikan untuk kelas Anda.</div></div></div>@endforelse</div>

    <!-- Papan Peringkat Kelas -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-trophy-fill me-2"></i>Peringkat Kelas {{ $student->classroom?->name ?? '-' }}</h6>
            <span class="text-muted small">Rata-rata nilai kuis</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70">Peringkat</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">Kuis Diikuti</th>
                            <th class="text-center">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaderboard as $row)
                            @php $isMe = $row->student_id === $student->id; @endphp
                            <tr class="{{ $isMe ? 'table-primary fw-semibold' : '' }}">
                                <td>
                                    @if($loop->iteration <= 3)
                                        <i class="bi bi-award-fill {{ $loop->iteration === 1 ? 'text-warning' : ($loop->iteration === 2 ? 'text-secondary' : 'text-danger') }}"></i>
                                    @endif
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    {{ $row->student?->name ?? '-' }}
                                    @if($isMe)<span class="badge bg-primary ms-1">Anda</span>@endif
                                </td>
                                <td class="text-center">{{ $row->quiz_count }}</td>
                                <td class="text-center fw-bold">{{ number_format((float) $row->average_score, 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Belum ada yang mengerjakan kuis di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
