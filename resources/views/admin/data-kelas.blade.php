@extends('layouts.app')

@section('title', 'Pembagian Kelas')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Pembagian Kelas</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kelas Baru
            </button>
        </div>

        <x-page-guide>Buat kelas baru dan tetapkan wali kelasnya di sini. Masukkan siswa ke kelas lewat tombol <strong>Atur Siswa</strong> (pilih satu per satu), atau sekaligus banyak lewat tombol <strong>Import</strong> di kartu kelas (file Excel berisi Nama, NIS, NISN).</x-page-guide>

        @if (session('classroom_import_result'))
            @php
                $importResult = session('classroom_import_result');
            @endphp
            <div class="alert {{ $importResult->hasErrors() ? 'alert-warning' : 'alert-success' }} alert-dismissible fade show" role="alert">
                <p class="fw-semibold mb-1"><i class="bi bi-clipboard-check me-1"></i>Hasil import ke Kelas {{ $importResult->classroomName }}:
                    {{ $importResult->placed }} siswa berhasil dimasukkan{{ $importResult->skipped ? ', '.count($importResult->skipped).' dilewati' : '' }}{{ $importResult->hasErrors() ? ', '.count($importResult->errors).' baris gagal' : '' }}.</p>
                @if ($importResult->hasErrors())
                    <p class="small mb-1 mt-2 fw-semibold">Baris yang gagal (tidak diproses):</p>
                    <ul class="mb-0 small">@foreach ($importResult->errors as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                @if ($importResult->skipped)
                    <p class="small mb-1 mt-2 fw-semibold">Dilewati:</p>
                    <ul class="mb-0 small">@foreach ($importResult->skipped as $note)<li>{{ $note }}</li>@endforeach</ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                @if ($errors->has('file'))<strong>File import tidak bisa diproses:</strong>@endif
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Grid Card Kelas -->
        <div class="row">
            @forelse ($classrooms as $classroom)
                @php
                    $percentage = $classroom->capacity > 0
                        ? (int) round(($classroom->students_count / $classroom->capacity) * 100)
                        : 0;
                    $borderColor = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'success' : 'primary');
                @endphp
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 border-start border-{{ $borderColor }} border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-{{ $borderColor }} mb-0">Kelas {{ $classroom->name }}</h5>
                                <span class="badge bg-{{ $borderColor }}-soft text-{{ $borderColor }}" style="background-color: #e7f1ff;">Kapasitas: {{ $classroom->capacity }}</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($classroom->homeroomTeacher->name ?? 'Belum Ada') }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <small class="text-muted d-block">Wali Kelas</small>
                                    <span class="fw-semibold text-dark">{{ $classroom->homeroomTeacher->name ?? 'Belum ditentukan' }}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Terisi: {{ $classroom->students_count }} Siswa</span>
                                    <span class="fw-bold text-{{ $borderColor }}">{{ $percentage >= 100 ? 'Penuh' : $percentage.'%' }}</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $borderColor }}" role="progressbar" style="width: {{ min($percentage, 100) }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalAturSiswa{{ $classroom->id }}"><i class="bi bi-people"></i> Atur Siswa</button>
                                <button class="btn btn-sm btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#modalImportSiswa{{ $classroom->id }}" title="Import siswa dari Excel"><i class="bi bi-file-earmark-excel"></i> Import</button>
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditKelas"
                                    data-action="{{ route('admin.pembagian-kelas.update', $classroom) }}"
                                    data-name="{{ $classroom->name }}"
                                    data-grade-level="{{ $classroom->grade_level }}"
                                    data-capacity="{{ $classroom->capacity }}"
                                    data-homeroom-teacher-id="{{ $classroom->homeroom_teacher_id }}"
                                ><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('admin.pembagian-kelas.destroy', $classroom) }}" method="POST" onsubmit="return confirm('Hapus kelas {{ $classroom->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Import Siswa: {{ $classroom->name }} -->
                <div class="modal fade" id="modalImportSiswa{{ $classroom->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form action="{{ route('admin.pembagian-kelas.import', $classroom) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel me-2"></i>Import Siswa ke Kelas {{ $classroom->name }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <ol class="small ps-3">
                                        <li>Klik <strong>Download Template Excel</strong>, lalu isi kolom <strong>Nama Siswa</strong>, <strong>NIS</strong>, dan <strong>NISN</strong> (satu siswa per baris).</li>
                                        <li>Siswa harus <strong>sudah terdaftar</strong> di Data Siswa — fitur ini tidak membuat siswa baru. Nama harus sama dengan data siswa.</li>
                                        <li>Upload file, lalu klik <strong>Import</strong>.</li>
                                    </ol>
                                    <p class="small text-muted">Baris yang benar langsung dimasukkan ke kelas ini. Baris yang salah (NIS/NISN tidak ditemukan, data tidak lengkap, siswa sudah di kelas lain, atau kelas penuh) dilewati dan ditampilkan alasannya.</p>
                                    <a href="{{ route('admin.pembagian-kelas.import.template', $classroom) }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download me-1"></i> Download Template Excel</a>
                                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i> Import</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Atur Siswa: {{ $classroom->name }} -->
                <div class="modal fade" id="modalAturSiswa{{ $classroom->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <form action="{{ route('admin.pembagian-kelas.assign-students', $classroom) }}" method="POST" class="form-atur-siswa">
                            @csrf
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title fw-bold"><i class="bi bi-people me-2"></i>Plotting Siswa - Kelas {{ $classroom->name }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @php
                                        $candidates = $activeStudents->where('classroom_id', '!==', $classroom->id);
                                        $candidateGroups = $candidates->groupBy(fn ($student) => $student->classroom ? 'Dari Kelas '.$student->classroom->name.' (akan dipindah)' : 'Belum Punya Kelas')
                                            ->sortKeysUsing(fn ($a, $b) => ($a === 'Belum Punya Kelas' ? -1 : ($b === 'Belum Punya Kelas' ? 1 : strnatcmp($a, $b))));
                                    @endphp
                                    <p class="text-muted small">Pilih siswa dari kolom kiri (tahan Ctrl/Cmd untuk memilih banyak), lalu klik panah kanan untuk memasukkan ke kelas ini. Siswa yang sudah punya kelas lain akan <strong>dipindah</strong> ke kelas ini. Gunakan panah kiri untuk mengeluarkan siswa. Jangan lupa klik <strong>Simpan Pembagian</strong>.</p>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="fw-semibold mb-2">Pilih Siswa ({{ $candidates->count() }})</label>
                                            <input type="search" class="form-control form-control-sm mb-2 search-available" placeholder="Cari nama atau NISN...">
                                            <select class="form-select select-available" multiple style="height: 300px;">
                                                @forelse ($candidateGroups as $groupLabel => $groupStudents)
                                                    <optgroup label="{{ $groupLabel }}">
                                                        @foreach ($groupStudents as $student)
                                                            <option value="{{ $student->id }}" data-search="{{ strtolower($student->name.' '.$student->nisn) }}">{{ $student->name }}{{ $student->classroom ? ' — '.$student->classroom->name : '' }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @empty
                                                    <option disabled>Semua siswa aktif sudah ada di kelas ini</option>
                                                @endforelse
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex flex-column justify-content-center align-items-center gap-3">
                                            <button type="button" class="btn btn-outline-primary w-100 btn-move-right"><i class="bi bi-arrow-right-circle fs-4"></i></button>
                                            <button type="button" class="btn btn-outline-danger w-100 btn-move-left"><i class="bi bi-arrow-left-circle fs-4"></i></button>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="fw-semibold mb-2">Anggota Kelas {{ $classroom->name }} ({{ $classroom->students->count() }})</label>
                                            <select name="student_ids[]" class="form-select select-members" multiple style="height: 300px;">
                                                @foreach ($classroom->students as $student)
                                                    <option value="{{ $student->id }}" selected>{{ $student->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="submit" class="btn btn-primary">Simpan Pembagian</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center text-muted">Belum ada kelas. Tambahkan kelas baru untuk mulai membagi siswa.</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Tambah Kelas -->
    <div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.pembagian-kelas.store') }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Kelas</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: 6-C" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tingkat Kelas</label>
                            <select name="grade_level" class="form-select" required>
                                @for ($grade = 1; $grade <= 6; $grade++)
                                    <option value="{{ $grade }}">Kelas {{ $grade }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Wali Kelas Penanggung Jawab</label>
                            <select name="homeroom_teacher_id" class="form-select">
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kapasitas Siswa</label>
                            <input type="number" name="capacity" class="form-control" value="30" min="1" max="60" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kelas -->
    <div class="modal fade" id="modalEditKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditKelas" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Kelas</label>
                            <input type="text" name="name" id="editKelasName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tingkat Kelas</label>
                            <select name="grade_level" id="editKelasGradeLevel" class="form-select" required>
                                @for ($grade = 1; $grade <= 6; $grade++)
                                    <option value="{{ $grade }}">Kelas {{ $grade }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Wali Kelas Penanggung Jawab</label>
                            <select name="homeroom_teacher_id" id="editKelasHomeroomTeacherId" class="form-select">
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kapasitas Siswa</label>
                            <input type="number" name="capacity" id="editKelasCapacity" class="form-control" min="1" max="60" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('modalEditKelas').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditKelas').action = button.dataset.action;
            document.getElementById('editKelasName').value = button.dataset.name;
            document.getElementById('editKelasGradeLevel').value = button.dataset.gradeLevel;
            document.getElementById('editKelasHomeroomTeacherId').value = button.dataset.homeroomTeacherId ?? '';
            document.getElementById('editKelasCapacity').value = button.dataset.capacity;
        });

        function moveSelectedOptions(from, to) {
            Array.from(from.selectedOptions).forEach(option => {
                option.selected = false;
                option.hidden = false;
                to.appendChild(option);
            });
        }

        document.querySelectorAll('.form-atur-siswa').forEach(function (form) {
            const available = form.querySelector('.select-available');
            const members = form.querySelector('.select-members');
            const search = form.querySelector('.search-available');

            form.querySelector('.btn-move-right').addEventListener('click', () => moveSelectedOptions(available, members));
            form.querySelector('.btn-move-left').addEventListener('click', () => moveSelectedOptions(members, available));

            // Saring daftar kiri berdasarkan nama/NISN.
            search.addEventListener('input', () => {
                const keyword = search.value.trim().toLowerCase();
                available.querySelectorAll('option[data-search]').forEach(option => {
                    option.hidden = keyword !== '' && ! option.dataset.search.includes(keyword);
                });
            });

            // select multiple hanya mengirim opsi yang sedang selected, jadi tandai semua opsi anggota sebelum submit.
            form.addEventListener('submit', function () {
                Array.from(members.options).forEach(option => option.selected = true);
            });
        });
    </script>
@endsection
