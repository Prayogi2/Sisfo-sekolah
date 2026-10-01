@extends('layouts.app')

@section('title', 'Absensi Kelas')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Absensi Kelas</h1>
            <p class="text-muted mb-0">Pantau siswa yang sudah masuk di kelas yang Anda walikan.</p>
        </div>
        @if($classroom && $classrooms->count() > 1)
            <form method="GET" action="{{ route('guru.absensi-kelas') }}">
                <input type="hidden" name="tampilan" value="{{ $isRecap ? 'rekap' : 'harian' }}">
                <select name="classroom" class="form-select fw-semibold" onchange="this.form.submit()" aria-label="Pilih kelas">
                    @foreach($classrooms as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $classroom->id)>Kelas {{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(! $classroom)
        <div class="alert alert-light border text-muted"><i class="bi bi-info-circle me-1"></i>Anda belum ditugaskan sebagai wali kelas, jadi belum ada absensi kelas untuk ditampilkan.</div>
    @else
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link {{ $isRecap ? '' : 'active' }}" href="{{ route('guru.absensi-kelas', ['classroom' => $classroom->id]) }}"><i class="bi bi-calendar-day me-1"></i>Harian</a></li>
            <li class="nav-item"><a class="nav-link {{ $isRecap ? 'active' : '' }}" href="{{ route('guru.absensi-kelas', ['classroom' => $classroom->id, 'tampilan' => 'rekap']) }}"><i class="bi bi-calendar3 me-1"></i>Rekap Bulanan / Semester</a></li>
        </ul>

        @if($isRecap)
            @include('guru.partials.absensi-rekap')
        @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="GET" action="{{ route('guru.absensi-kelas') }}" class="d-flex gap-2">
                <input type="hidden" name="classroom" value="{{ $classroom->id }}">
                <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Tanggal absensi">
            </form>
            <div class="d-flex gap-2">
                <a href="{{ route('guru.absensi-kelas.unduh', ['classroom' => $classroom->id, 'format' => 'xlsx', 'date' => $date->toDateString()]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a href="{{ route('guru.absensi-kelas.unduh', ['classroom' => $classroom->id, 'format' => 'pdf', 'date' => $date->toDateString()]) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            </div>
        </div>

        <x-page-guide>Status terisi otomatis dari scan kartu di pos presensi. Siswa yang lupa membawa kartu atau keterangannya berubah bisa dikoreksi lewat kolom <strong>Ubah Status</strong>, lalu klik <strong>Simpan Perubahan</strong>.</x-page-guide>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-4">
                <div>
                    <div class="text-muted small">Kelas {{ $classroom->name }} · {{ $date->translatedFormat('l, d F Y') }}</div>
                    <div class="fs-3 fw-bold">Masuk: <span class="text-success">{{ $inSchoolCount }}</span> <span class="fs-5 text-muted">dari {{ $students->count() }} siswa</span></div>
                </div>
                <div class="d-flex flex-wrap gap-2 ms-lg-auto">
                    @foreach($statuses as $status)
                        <span class="badge rounded-pill bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis border border-{{ $status->color() }}-subtle px-3 py-2">{{ $status->label() }}: {{ $summary[$status->value] }}</span>
                    @endforeach
                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border px-3 py-2">Belum Absen: {{ $summary['belum'] }}</span>
                </div>
            </div>
        </div>

        <form action="{{ route('guru.absensi-kelas.simpan', $classroom) }}" method="POST" class="card border-0 shadow-sm">
            @csrf
            @method('PUT')
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th style="width: 50px;">No</th><th>Nama Siswa</th><th>NIS</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th style="min-width: 170px;">Ubah Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $attendance = $attendances->get($student->id);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $student->name }}</td>
                                    <td class="text-muted">{{ $student->nis ?: '-' }}</td>
                                    <td>{{ $attendance?->check_in_at?->format('H:i') ?? '-' }}</td>
                                    <td>{{ $attendance?->check_out_at?->format('H:i') ?? '-' }}</td>
                                    <td>
                                        @if($attendance)
                                            <span class="badge bg-{{ $attendance->status->color() }}">{{ $attendance->status->label() }}</span>
                                            @if(! $attendance->check_in_at && $attendance->status->isInSchool())
                                                <div class="small text-muted">diisi manual</div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Belum Absen</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="statuses[{{ $student->id }}]" class="form-select form-select-sm" aria-label="Ubah status {{ $student->name }}">
                                            <option value="">— Tidak diubah —</option>
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->value }}" @selected(old("statuses.{$student->id}") === $status->value)>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada siswa aktif di kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($students->isNotEmpty())
                <div class="card-footer bg-white d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                </div>
            @endif
        </form>
        @endif
    @endif
</div>
@endsection
