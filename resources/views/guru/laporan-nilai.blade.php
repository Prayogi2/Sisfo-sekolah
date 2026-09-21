@extends('layouts.app')

@section('title', 'Laporan & Rekap Nilai Siswa')

@section('content')
<div class="container-fluid">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Laporan & Rekap Nilai Siswa</h4>
            <p class="text-muted mb-0">Rekapitulasi nilai tugas, kuis, UTS, UAS, dan status kelulusan siswa per kelas.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Ringkasan Statistik Nilai -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 fs-4">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Siswa Dinilai</span>
                        <h4 class="fw-bold mb-0">{{ $stats['dinilai'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3 fs-4">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Rata-Rata Nilai Kelas</span>
                        <h4 class="fw-bold mb-0">{{ $stats['rata_rata'] ?? '-' }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 me-3 fs-4">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Nilai Tertinggi</span>
                        <h4 class="fw-bold mb-0">{{ $stats['tertinggi'] ?? '-' }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 me-3 fs-4">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Perlu Remedial (KKM {{ $passingScore }})</span>
                        <h4 class="fw-bold mb-0">{{ $stats['remedial'] }} Siswa</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Pencarian -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Tahun Ajaran</label>
                    <input type="text" class="form-control" name="academic_year" value="{{ $academicYear }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold">Semester</label>
                    <select class="form-select" name="semester">
                        @foreach(\App\Enums\Semester::cases() as $option)
                            <option value="{{ $option->value }}" @selected($semester === $option)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select class="form-select" name="classroom_id">
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected($classroomId === $classroom->id)>{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <select class="form-select" name="subject_id">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($subjects->isEmpty())
        <div class="alert alert-info border-0 shadow-sm">
            Anda belum ditugaskan mengampu mata pelajaran apa pun. Hubungi admin untuk penugasan mapel.
        </div>
    @else
        <!-- Tabel Rekapitulasi Nilai -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-table text-primary me-2"></i>
                    Data Nilai Siswa
                    @php
                        $activeSubject = $subjects->firstWhere('id', $subjectId);
                        $activeClassroom = $classrooms->firstWhere('id', $classroomId);
                    @endphp
                    @if($activeClassroom && $activeSubject)
                        : {{ $activeClassroom->name }} ({{ $activeSubject->name }})
                    @endif
                    <span class="text-muted fw-normal ms-2 small">
                        Bobot: Tugas {{ $weight->assignment_weight }}% · Kuis {{ $weight->quiz_weight }}% ·
                        UTS {{ $weight->midterm_weight }}% · UAS {{ $weight->final_weight }}%
                    </span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Tugas ({{ $weight->assignment_weight }}%)</th>
                                <th class="text-center">Kuis ({{ $weight->quiz_weight }}%)</th>
                                <th class="text-center">UTS ({{ $weight->midterm_weight }}%)</th>
                                <th class="text-center">UAS ({{ $weight->final_weight }}%)</th>
                                <th class="text-center">Nilai Akhir</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $grade = $grades->get($student->id);
                                    $finalScore = $grade?->finalScore($weight);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->nisn }}</td>
                                    <td class="fw-semibold">{{ $student->name }}</td>
                                    <td class="text-center">{{ $grade?->assignment_score ?? '-' }}</td>
                                    <td class="text-center">{{ $grade?->quiz_score ?? '-' }}</td>
                                    <td class="text-center">{{ $grade?->midterm_score ?? '-' }}</td>
                                    <td class="text-center">{{ $grade?->final_score ?? '-' }}</td>
                                    <td class="text-center fw-bold {{ $finalScore !== null && $finalScore < $passingScore ? 'text-danger' : 'text-primary' }}">
                                        {{ $finalScore ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($finalScore !== null)
                                            <span class="badge bg-primary">{{ \App\Models\Grade::letterFor($finalScore) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($finalScore === null)
                                            <span class="badge bg-secondary">Belum Dinilai</span>
                                        @elseif($finalScore >= $passingScore)
                                            <span class="badge bg-success">Lulus</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Remedial</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInputNilai"
                                                data-student-id="{{ $student->id }}"
                                                data-student-name="{{ $student->name }}"
                                                data-assignment="{{ $grade?->assignment_score }}"
                                                data-quiz="{{ $grade?->quiz_score }}"
                                                data-midterm="{{ $grade?->midterm_score }}"
                                                data-final="{{ $grade?->final_score }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">Belum ada siswa di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal Form Input / Edit Nilai -->
<div class="modal fade" id="modalInputNilai" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Input / Edit Nilai Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('guru.laporan-nilai.simpan') }}" method="POST">
                @csrf
                <input type="hidden" name="student_id" id="nilaiStudentId">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="semester" value="{{ $semester->value }}">
                <div class="modal-body">
                    <p class="mb-3">Siswa: <strong id="nilaiStudentName"></strong></p>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai Tugas ({{ $weight->assignment_weight }}%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="assignment_score" id="nilaiAssignment">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai Kuis ({{ $weight->quiz_weight }}%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="quiz_score" id="nilaiQuiz">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai UTS ({{ $weight->midterm_weight }}%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="midterm_score" id="nilaiMidterm">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Nilai UAS ({{ $weight->final_weight }}%)</label>
                            <input type="number" min="0" max="100" class="form-control" name="final_score" id="nilaiFinal">
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        Komponen yang dikosongkan tidak dihitung sebagai nol — bobotnya dikeluarkan dari perhitungan nilai akhir.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Nilai</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('modalInputNilai')?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        document.getElementById('nilaiStudentId').value = button.dataset.studentId ?? '';
        document.getElementById('nilaiStudentName').textContent = button.dataset.studentName ?? '';
        document.getElementById('nilaiAssignment').value = button.dataset.assignment ?? '';
        document.getElementById('nilaiQuiz').value = button.dataset.quiz ?? '';
        document.getElementById('nilaiMidterm').value = button.dataset.midterm ?? '';
        document.getElementById('nilaiFinal').value = button.dataset.final ?? '';
    });
</script>
@endsection
