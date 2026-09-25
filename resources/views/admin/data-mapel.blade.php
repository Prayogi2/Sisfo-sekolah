@extends('layouts.app')

@section('title', 'Kelola Mapel')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Kelola Mata Pelajaran</h1>
                <p class="text-muted mb-0">Daftar mata pelajaran yang diajarkan di MIS Nurul Falaq.</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMapel">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Mapel
            </button>
        </div>

        <x-page-guide>Daftar mapel di sini dipakai saat menetapkan mapel yang diajarkan guru, dan saat guru membuat soal/kuis. Klik ikon <i class="bi bi-person-video3 text-success"></i> untuk mengatur <strong>Guru Pengampu</strong> tiap mapel beserta kelas yang diajarnya.</x-page-guide>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Tabel Mapel -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Mata Pelajaran ({{ $subjects->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">No</th>
                                <th width="150">Kode</th>
                                <th>Nama Mata Pelajaran</th>
                                <th>Guru Pengampu</th>
                                <th class="text-center">Bobot Nilai (T/K/UTS/UAS)</th>
                                <th class="text-center" width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $subject)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-primary-soft text-primary" style="background-color: #e7f1ff;">{{ $subject->code }}</span></td>
                                    <td class="fw-semibold text-dark">{{ $subject->name }}</td>
                                    @php
                                        $pengampu = $subject->teachingAssignments->groupBy('teacher_id');
                                    @endphp
                                    <td>
                                        @forelse ($pengampu as $rows)
                                            <div class="small">
                                                <i class="bi bi-person-fill text-primary me-1"></i><span class="fw-semibold">{{ $rows->first()->teacher->name }}</span>
                                                <span class="text-muted">({{ $rows->pluck('classroom.name')->sort()->join(', ') }})</span>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Belum ada guru pengampu</span>
                                        @endforelse
                                    </td>
                                    @php
                                        $weight = $weights->get($subject->id)
                                            ?? \App\Models\GradeWeight::default($subject->id, $academicYear, $semester);
                                        $isCustomWeight = $weights->has($subject->id);
                                    @endphp
                                    <td class="text-center">
                                        {{ $weight->assignment_weight }}/{{ $weight->quiz_weight }}/{{ $weight->midterm_weight }}/{{ $weight->final_weight }}
                                        @unless ($isCustomWeight)
                                            <span class="badge bg-secondary ms-1">bawaan</span>
                                        @endunless
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-light" title="Atur Guru Pengampu"
                                            data-bs-toggle="modal" data-bs-target="#modalGuruPengampu"
                                            data-action="{{ route('admin.data-mapel.guru-pengampu', $subject) }}"
                                            data-subject="{{ $subject->name }}"
                                            data-assignments="{{ $pengampu->map(fn ($rows, $teacherId) => ['teacher_id' => $teacherId, 'classroom_ids' => $rows->pluck('classroom_id')->values()])->values()->toJson() }}"
                                        ><i class="bi bi-person-video3 text-success"></i></button>
                                        <button type="button" class="btn btn-sm btn-light" title="Atur Bobot Nilai"
                                            data-bs-toggle="modal" data-bs-target="#modalBobotNilai"
                                            data-action="{{ route('admin.data-mapel.bobot-nilai', $subject) }}"
                                            data-subject="{{ $subject->name }}"
                                            data-assignment="{{ $weight->assignment_weight }}"
                                            data-quiz="{{ $weight->quiz_weight }}"
                                            data-midterm="{{ $weight->midterm_weight }}"
                                            data-final="{{ $weight->final_weight }}"
                                        ><i class="bi bi-sliders text-primary"></i></button>
                                        <button type="button" class="btn btn-sm btn-light" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEditMapel"
                                            data-action="{{ route('admin.data-mapel.update', $subject) }}"
                                            data-code="{{ $subject->code }}"
                                            data-name="{{ $subject->name }}"
                                        ><i class="bi bi-pencil-square text-warning"></i></button>
                                        <form action="{{ route('admin.data-mapel.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus mata pelajaran {{ $subject->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada mata pelajaran. Tambahkan mapel baru untuk mulai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atur Bobot Nilai -->
    <div class="modal fade" id="modalBobotNilai" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formBobotNilai" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="semester" value="{{ $semester->value }}">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2"></i>Atur Bobot Nilai</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Mapel: <strong id="bobotSubjectName"></strong><br>
                            <span class="text-muted small">Berlaku untuk semester {{ $semester->label() }} {{ $academicYear }}.</span>
                        </p>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Bobot Tugas (%)</label>
                                <input type="number" min="0" max="100" name="assignment_weight" id="bobotAssignment" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Bobot Kuis (%)</label>
                                <input type="number" min="0" max="100" name="quiz_weight" id="bobotQuiz" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Bobot UTS (%)</label>
                                <input type="number" min="0" max="100" name="midterm_weight" id="bobotMidterm" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Bobot UAS (%)</label>
                                <input type="number" min="0" max="100" name="final_weight" id="bobotFinal" class="form-control" required>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Total keempat bobot harus tepat 100%.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Bobot</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Mapel -->
    <div class="modal fade" id="modalTambahMapel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.data-mapel.store') }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Mata Pelajaran</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Mapel</label>
                            <input type="text" name="code" class="form-control" placeholder="Contoh: MTK" maxlength="20" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Mata Pelajaran</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Matematika" required>
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

    <!-- Modal Edit Mapel -->
    <div class="modal fade" id="modalEditMapel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditMapel" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Mata Pelajaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Mapel</label>
                            <input type="text" name="code" id="editMapelCode" class="form-control" maxlength="20" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Mata Pelajaran</label>
                            <input type="text" name="name" id="editMapelName" class="form-control" required>
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

    <!-- Template baris guru pengampu (guru + kelas yang diajar) -->
    <template id="templatePengampuRow">
        <div class="row g-2 align-items-start mb-2 pengampu-row">
            <div class="col-md-5">
                <select name="assignments[__INDEX__][teacher_id]" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Guru --</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <select name="assignments[__INDEX__][classroom_ids][]" class="form-select form-select-sm" multiple required size="3">
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm remove-pengampu-row" title="Hapus baris"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    <!-- Modal Guru Pengampu -->
    <div class="modal fade" id="modalGuruPengampu" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formGuruPengampu" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-video3 me-2"></i>Guru Pengampu</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Mapel: <strong id="pengampuSubjectName"></strong></p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Guru & Kelas yang Diajar</label>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addPengampuRow"><i class="bi bi-plus-lg"></i> Tambah Guru</button>
                        </div>
                        <div id="pengampuRows"></div>
                        <p class="text-muted small mb-0">Satu mapel bisa diajar guru berbeda di kelas berbeda — tambahkan satu baris per guru, lalu pilih kelasnya (tahan Ctrl/Cmd untuk pilih lebih dari satu). Data ini sama dengan "Mata Pelajaran & Kelas Diajarkan" di menu Data Guru, dan menentukan kelas mana yang bisa diakses guru untuk kuis & nilai.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Guru Pengampu</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const template = document.getElementById('templatePengampuRow');
            const container = document.getElementById('pengampuRows');
            let rowIndex = 0;

            function addPengampuRow(teacherId, classroomIds) {
                const fragment = template.content.cloneNode(true);
                fragment.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace('__INDEX__', rowIndex);
                });
                rowIndex++;

                const row = fragment.querySelector('.pengampu-row');
                if (teacherId) {
                    row.querySelector('select[name$="[teacher_id]"]').value = teacherId;
                }
                Array.from(row.querySelector('select[name$="[classroom_ids][]"]').options).forEach((option) => {
                    option.selected = (classroomIds || []).map(String).includes(option.value);
                });
                row.querySelector('.remove-pengampu-row').addEventListener('click', () => row.remove());
                container.appendChild(fragment);
            }

            document.getElementById('addPengampuRow').addEventListener('click', () => addPengampuRow(null, []));

            document.getElementById('modalGuruPengampu').addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('formGuruPengampu').action = button.dataset.action;
                document.getElementById('pengampuSubjectName').textContent = button.dataset.subject;
                container.innerHTML = '';
                rowIndex = 0;
                const assignments = JSON.parse(button.dataset.assignments || '[]');
                if (assignments.length === 0) {
                    addPengampuRow(null, []);
                } else {
                    assignments.forEach((assignment) => addPengampuRow(assignment.teacher_id, assignment.classroom_ids));
                }
            });
        })();

        document.getElementById('modalEditMapel').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditMapel').action = button.dataset.action;
            document.getElementById('editMapelCode').value = button.dataset.code;
            document.getElementById('editMapelName').value = button.dataset.name;
        });

        document.getElementById('modalBobotNilai').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formBobotNilai').action = button.dataset.action;
            document.getElementById('bobotSubjectName').textContent = button.dataset.subject;
            document.getElementById('bobotAssignment').value = button.dataset.assignment;
            document.getElementById('bobotQuiz').value = button.dataset.quiz;
            document.getElementById('bobotMidterm').value = button.dataset.midterm;
            document.getElementById('bobotFinal').value = button.dataset.final;
        });
    </script>
@endsection
