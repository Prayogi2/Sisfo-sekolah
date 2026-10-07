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
                $importErrors = $importResult['errors'] ?? [];
                $importSkipped = $importResult['skipped'] ?? [];
            @endphp
            <div class="alert {{ $importErrors ? 'alert-warning' : 'alert-success' }} alert-dismissible fade show" role="alert">
                <p class="fw-semibold mb-1"><i class="bi bi-clipboard-check me-1"></i>Hasil import ke Kelas {{ $importResult['classroomName'] ?? '-' }}:
                    {{ $importResult['placed'] ?? 0 }} siswa berhasil dimasukkan{{ $importSkipped ? ', '.count($importSkipped).' dilewati' : '' }}{{ $importErrors ? ', '.count($importErrors).' baris gagal' : '' }}.</p>
                @if ($importErrors)
                    <p class="small mb-1 mt-2 fw-semibold">Baris yang gagal (tidak diproses):</p>
                    <ul class="mb-0 small">@foreach ($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                @if ($importSkipped)
                    <p class="small mb-1 mt-2 fw-semibold">Dilewati:</p>
                    <ul class="mb-0 small">@foreach ($importSkipped as $note)<li>{{ $note }}</li>@endforeach</ul>
                @endif
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
            <form action="{{ route('admin.pembagian-kelas.store') }}" method="POST" id="formTambahKelas">
                @csrf
                <input type="hidden" name="_form" value="tambah-kelas">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Kelas Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Kelas</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: 6-C" value="{{ old('_form') === 'tambah-kelas' ? old('name') : '' }}" required>
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
                            <label for="newClassStudentInput" class="form-label fw-semibold">NISN/NIS Siswa <span class="text-muted fw-normal small">(opsional)</span></label>
                            <div class="input-group">
                                <textarea id="newClassStudentInput" class="form-control" rows="3" placeholder="Ketik atau tempel NISN/NIS — satu per baris, atau pisahkan dengan koma" autocomplete="off"></textarea>
                                <button type="button" class="btn btn-primary" id="newClassStudentAdd"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                            </div>
                            <div class="form-text">Bisa banyak sekaligus, mis. tempel satu kolom NISN dari Excel. <kbd>Enter</kbd> = Tambah, <kbd>Shift</kbd>+<kbd>Enter</kbd> = baris baru.</div>
                            <div class="form-text text-success" id="newClassStudentSuccess" hidden></div>
                            <div class="form-text text-danger" id="newClassStudentError" hidden></div>
                            <ul class="list-group mt-2" id="newClassStudentList">
                                @foreach ($oldNewClassStudents as $student)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2" data-student-id="{{ $student->id }}">
                                        <span><span class="fw-semibold">{{ $student->name }}</span> <small class="text-muted">· {{ $student->nisn ?: $student->nis }}</small></span>
                                        <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                                        <button type="button" class="btn-close btn-sm" aria-label="Hapus {{ $student->name }}"></button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="form-text" id="newClassStudentCount"></div>
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
        // Tambah Kelas: cari banyak siswa sekaligus per NISN/NIS lewat AJAX, lalu kumpulkan jadi daftar anggota kelas.
        (function () {
            const input = document.getElementById('newClassStudentInput');
            const addButton = document.getElementById('newClassStudentAdd');
            const successText = document.getElementById('newClassStudentSuccess');
            const errorText = document.getElementById('newClassStudentError');
            const list = document.getElementById('newClassStudentList');
            const count = document.getElementById('newClassStudentCount');
            const maxStudents = {{ \App\Models\Classroom::DEFAULT_CAPACITY }};
            const lookupUrl = @json(route('admin.pembagian-kelas.cari-siswa'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function showSuccess(message) {
                successText.textContent = message;
                successText.hidden = ! message;
            }

            // Satu pesan, atau daftar pesan per NISN yang gagal.
            function showError(messages) {
                const lines = [].concat(messages || []).filter(Boolean);
                errorText.replaceChildren();
                if (lines.length === 1) {
                    errorText.textContent = lines[0];
                } else if (lines.length > 1) {
                    const listElement = document.createElement('ul');
                    listElement.className = 'mb-0 ps-3';
                    lines.forEach((line) => listElement.appendChild(Object.assign(document.createElement('li'), { textContent: line })));
                    errorText.appendChild(listElement);
                }
                errorText.hidden = lines.length === 0;
            }

            function updateCount() {
                const total = list.children.length;
                count.textContent = total ? `${total} siswa akan dimasukkan ke kelas ini saat disimpan.` : 'Boleh dikosongkan — siswa bisa ditambahkan belakangan.';
            }

            function addStudentRow(student) {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
                item.dataset.studentId = student.id;
                const label = document.createElement('span');
                label.innerHTML = '<span class="fw-semibold"></span> <small class="text-muted"></small>';
                label.children[0].textContent = student.name;
                label.children[1].textContent = '· ' + (student.nisn || student.nis);
                const hidden = Object.assign(document.createElement('input'), { type: 'hidden', name: 'student_ids[]', value: student.id });
                const remove = Object.assign(document.createElement('button'), { type: 'button', className: 'btn-close btn-sm' });
                remove.setAttribute('aria-label', 'Hapus ' + student.name);
                item.append(label, hidden, remove);
                list.appendChild(item);
                updateCount();
            }

            async function lookup() {
                const keywords = input.value.trim();
                showSuccess('');
                if (! keywords) {
                    return showError('Ketik NISN atau NIS siswa terlebih dahulu.');
                }
                if (list.children.length >= maxStudents) {
                    return showError(`Daftar sudah penuh (maksimal ${maxStudents} siswa, kapasitas kelas baru).`);
                }

                addButton.disabled = true;
                try {
                    const response = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ keywords }),
                    });
                    const data = await response.json();
                    if (! response.ok) {
                        return showError(data.errors?.keywords?.[0] || data.message || 'Siswa tidak bisa dicari.');
                    }

                    const failed = data.errors.map((error) => error.message);
                    const failedKeywords = data.errors.map((error) => error.keyword);
                    let added = 0;
                    data.students.forEach((student) => {
                        if (list.querySelector(`[data-student-id="${student.id}"]`)) {
                            failed.push(`${student.name} sudah ada di daftar.`);
                        } else if (list.children.length >= maxStudents) {
                            failed.push(`${student.name} tidak ditambahkan: daftar sudah penuh (maksimal ${maxStudents} siswa).`);
                            failedKeywords.push(student.nisn || student.nis);
                        } else {
                            addStudentRow(student);
                            added++;
                        }
                    });

                    showSuccess(added ? `${added} siswa ditambahkan ke daftar.` : '');
                    showError(failed);
                    // Sisakan hanya NISN yang gagal supaya mudah dibetulkan lalu ditambah ulang.
                    input.value = failedKeywords.join('\n');
                } catch (error) {
                    showError('Gagal menghubungi server. Coba lagi.');
                } finally {
                    addButton.disabled = false;
                    input.focus();
                }
            }

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && ! event.shiftKey) {
                    event.preventDefault(); // Enter = Tambah (bukan simpan form); Shift+Enter = baris baru.
                    lookup();
                }
            });
            addButton.addEventListener('click', lookup);
            list.addEventListener('click', (event) => {
                if (event.target.classList.contains('btn-close')) {
                    event.target.closest('li').remove();
                    updateCount();
                }
            });
            document.getElementById('modalTambahKelas').addEventListener('shown.bs.modal', () => {
                showError('');
                showSuccess('');
            });
            updateCount();

            // Simpan gagal validasi: buka lagi modalnya dengan isian sebelumnya.
            @if (old('_form') === 'tambah-kelas' && $errors->any())
                document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahKelas')).show());
            @endif
        })();

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
