@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Data Siswa</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.data-siswa.import') }}" class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
                </a>
                <a href="{{ route('admin.data-siswa.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Siswa
                </a>
            </div>
        </div>

        <x-page-guide>Tambah siswa satu per satu, atau sekaligus banyak lewat <strong>Import Excel</strong>. Hanya identitas inti yang wajib diisi — data Buku Induk lainnya bisa dilengkapi menyusul.</x-page-guide>

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

        @if (session('import_result'))
            @php($importResult = session('import_result'))
            <div class="alert {{ $importResult->hasErrors() ? 'alert-warning' : 'alert-success' }} alert-dismissible fade show" role="alert">
                <p class="fw-semibold mb-1">Impor selesai: {{ $importResult->imported }} siswa berhasil ditambahkan{{ $importResult->hasErrors() ? ', '.count($importResult->errors).' baris gagal.' : '.' }}</p>
                @if ($importResult->hasErrors())
                    <ul class="mb-0 small">
                        @foreach ($importResult->errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filter & Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.data-siswa') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Cari Siswa</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Nama atau NISN...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Siswa Aktif ({{ $students->total() }} Siswa)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Kelamin</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td>{{ $loop->iteration + $students->firstItem() - 1 }}</td>
                                    <td>{{ $student->nisn }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35" alt="">
                                            <span class="fw-semibold text-dark">{{ $student->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $student->classroom?->name ?? '-' }}</td>
                                    <td>{{ $student->gender->value === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                    <td>
                                        @if ($student->status->value === 'active')
                                            <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-soft text-danger" style="background-color: #ffeaea;">{{ $student->status->value === 'graduated' ? 'Lulus' : 'Nonaktif' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-light" title="Detail"
                                            data-bs-toggle="modal" data-bs-target="#modalDetailSiswa"
                                            data-name="{{ $student->name }}"
                                            data-nisn="{{ $student->nisn }}"
                                            data-nis="{{ $student->nis }}"
                                            data-classroom="{{ $student->classroom?->name ?? '-' }}"
                                            data-gender="{{ $student->gender->value === 'L' ? 'Laki-laki' : 'Perempuan' }}"
                                            data-birth-date="{{ $student->birth_date?->translatedFormat('d F Y') ?? '-' }}"
                                            data-address="{{ $student->address ?? '-' }}"
                                        ><i class="bi bi-eye text-primary"></i></button>
                                        <a href="{{ route('admin.data-siswa.kartu-qr', $student) }}" class="btn btn-sm btn-light" title="Cetak Kartu QR" target="_blank"><i class="bi bi-qr-code text-success"></i></a>
                                        <button type="button" class="btn btn-sm btn-light" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEditSiswa"
                                            data-action="{{ route('admin.data-siswa.update', $student) }}"
                                            data-nisn="{{ $student->nisn }}"
                                            data-nis="{{ $student->nis }}"
                                            data-name="{{ $student->name }}"
                                            data-gender="{{ $student->gender->value }}"
                                            data-classroom-id="{{ $student->classroom_id }}"
                                            data-address="{{ $student->address }}"
                                        ><i class="bi bi-pencil-square text-warning"></i></button>
                                        <form action="{{ route('admin.data-siswa.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data {{ $student->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())
                    <div class="card-footer bg-white">
                        {{ $students->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Edit Siswa -->
    <div class="modal fade" id="modalEditSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formEditSiswa" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NISN</label>
                                <input type="text" name="nisn" id="editNisn" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NIS</label>
                                <input type="text" name="nis" id="editNis" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="name" id="editName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jenis Kelamin</label>
                                <select name="gender" id="editGender" class="form-select" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kelas</label>
                                <select name="classroom_id" id="editClassroomId" class="form-select">
                                    <option value="">-- Belum Ada Kelas --</option>
                                    @foreach ($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Alamat</label>
                                <textarea name="address" id="editAddress" class="form-control" rows="2"></textarea>
                            </div>
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

    <!-- Modal Detail Siswa -->
    <div class="modal fade" id="modalDetailSiswa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-primary">Detail Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="detailAvatar" src="" class="rounded-circle mb-3 shadow-sm" width="100" height="100" alt="">
                    <h4 class="fw-bold text-dark" id="detailName"></h4>
                    <p class="text-muted" id="detailNisnNis"></p>
                    <span class="badge bg-primary-soft text-primary mb-3" id="detailClassroom"></span>
                </div>
                <div class="modal-footer bg-light d-block">
                    <div class="row text-start">
                        <div class="col-6 mb-2"><small class="text-muted d-block">Jenis Kelamin</small><span class="fw-semibold" id="detailGender"></span></div>
                        <div class="col-6 mb-2"><small class="text-muted d-block">Tanggal Lahir</small><span class="fw-semibold" id="detailBirthDate"></span></div>
                        <div class="col-12"><small class="text-muted d-block">Alamat</small><span class="fw-semibold" id="detailAddress"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('modalDetailSiswa').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('detailAvatar').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(button.dataset.name) + '&size=100&background=e7f1ff&color=0d6efd&bold=true';
            document.getElementById('detailName').textContent = button.dataset.name;
            document.getElementById('detailNisnNis').textContent = 'NISN: ' + button.dataset.nisn + ' | NIS: ' + button.dataset.nis;
            document.getElementById('detailClassroom').textContent = 'Kelas ' + button.dataset.classroom;
            document.getElementById('detailGender').textContent = button.dataset.gender;
            document.getElementById('detailBirthDate').textContent = button.dataset.birthDate;
            document.getElementById('detailAddress').textContent = button.dataset.address;
        });

        document.getElementById('modalEditSiswa').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditSiswa').action = button.dataset.action;
            document.getElementById('editNisn').value = button.dataset.nisn;
            document.getElementById('editNis').value = button.dataset.nis;
            document.getElementById('editName').value = button.dataset.name;
            document.getElementById('editGender').value = button.dataset.gender;
            document.getElementById('editClassroomId').value = button.dataset.classroomId ?? '';
            document.getElementById('editAddress').value = button.dataset.address ?? '';
        });
    </script>
@endsection
