@extends('layouts.app')

@section('title', 'Laporan Nilai Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Rekapitulasi Nilai Siswa</h1>
            <div class="btn-group">
                <a href="{{ route('admin.laporan-nilai.export.csv', request()->query()) }}" class="btn btn-outline-success shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="{{ route('admin.laporan-nilai.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
            </div>
        </div>

        <x-page-guide>Nilai diisi guru lewat menunya sendiri; halaman ini hanya untuk memantau dan mengekspor rekapnya. Pilih kelas, mapel, tahun ajaran & semester di filter untuk mempersempit tampilan.</x-page-guide>

        <!-- Filter Laporan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tahun Ajaran</label>
                        <input type="text" class="form-control" name="academic_year" value="{{ $academicYear }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Semester</label>
                        <select class="form-select" name="semester">
                            @foreach(\App\Enums\Semester::cases() as $option)
                                <option value="{{ $option->value }}" @selected($semester === $option)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Kelas</label>
                        <select class="form-select" name="classroom_id">
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected($classroomId === $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Mata Pelajaran</label>
                        <select class="form-select" name="subject_id">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Siswa Dinilai</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['dinilai'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Rata-Rata</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['rata_rata'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Nilai Tertinggi</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['tertinggi'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Perlu Remedial (KKM {{ $passingScore }})</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['remedial'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap Nilai -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">
                    @php
                        $activeSubject = $subjects->firstWhere('id', $subjectId);
                        $activeClassroom = $classrooms->firstWhere('id', $classroomId);
                    @endphp
                    Rekap Nilai
                    @if($activeSubject && $activeClassroom)
                        : {{ $activeSubject->name }} ({{ $activeClassroom->name }})
                    @endif
                    - Semester {{ $semester->label() }} {{ $academicYear }}
                    <span class="text-muted fw-normal ms-2 small">
                        Bobot {{ $weight->assignment_weight }}/{{ $weight->quiz_weight }}/{{ $weight->midterm_weight }}/{{ $weight->final_weight }}
                    </span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>NISN</th>
                                <th class="text-start">Nama Siswa</th>
                                <th>Tugas</th>
                                <th>Kuis</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Nilai Akhir</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $grade = $grades->get($student->id);
                                    $finalScore = $grade?->finalScore($weight);
                                @endphp
                                <tr>
                                    <td>{{ $student->nisn }}</td>
                                    <td class="text-start fw-semibold">{{ $student->name }}</td>
                                    <td>{{ $grade?->assignment_score ?? '-' }}</td>
                                    <td>{{ $grade?->quiz_score ?? '-' }}</td>
                                    <td>{{ $grade?->midterm_score ?? '-' }}</td>
                                    <td>{{ $grade?->final_score ?? '-' }}</td>
                                    <td class="fw-bold {{ $finalScore !== null && $finalScore < $passingScore ? 'text-danger' : 'text-primary' }}">
                                        {{ $finalScore ?? '-' }}
                                    </td>
                                    <td>
                                        @if($finalScore !== null)
                                            <span class="badge bg-primary">{{ \App\Models\Grade::letterFor($finalScore) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($finalScore === null)
                                            <span class="badge bg-secondary">Belum Dinilai</span>
                                        @elseif($finalScore >= $passingScore)
                                            <span class="badge bg-success">Lulus</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Remedial</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-muted py-4">Belum ada data nilai untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
