@extends('layouts.app')

@section('title', 'Data Guru & Wali Kelas')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Manajemen Data Guru & Wali Kelas</h1>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Guru Baru
            </button>
        </div>

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

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-primary border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Guru</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $teachers->count() }} Orang</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Wali Kelas</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $teachers->filter(fn ($teacher) => $teacher->homeroomClassrooms->isNotEmpty())->count() }} Orang</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Guru Aktif</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $teachers->where('is_active', true)->count() }} Orang</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Tenaga Pendidik</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIP / NUPTK</th>
                                <th>Nama Lengkap</th>
                                <th>Mata Pelajaran</th>
                                <th>Status</th>
                                <th>Kontak</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->nip }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($teacher->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                            <span class="fw-semibold text-dark">{{ $teacher->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $teacher->subjects->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td>
                                        @if ($teacher->homeroomClassrooms->isNotEmpty())
                                            <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Wali Kelas {{ $teacher->homeroomClassrooms->pluck('name')->join(', ') }}</span>
                                        @else
                                            <span class="badge bg-primary-soft text-primary">Guru Mapel</span>
                                        @endif
                                    </td>
                                    <td>{{ $teacher->phone ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-light" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEditGuru"
                                            data-action="{{ route('admin.data-guru.update', $teacher) }}"
                                            data-nip="{{ $teacher->nip }}"
                                            data-name="{{ $teacher->name }}"
                                            data-gender="{{ $teacher->gender->value }}"
                                            data-phone="{{ $teacher->phone }}"
                                            data-email="{{ $teacher->email }}"
                                            data-address="{{ $teacher->address }}"
                                            data-subject-ids="{{ $teacher->subjects->pluck('id')->join(',') }}"
                                        ><i class="bi bi-pencil-square text-warning"></i></button>
                                        <form action="{{ route('admin.data-guru.reset-password', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('Reset password login {{ $teacher->name }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light" title="Reset Password"><i class="bi bi-key text-secondary"></i></button>
                                        </form>
                                        <form action="{{ route('admin.data-guru.destroy', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data {{ $teacher->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada data guru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Guru -->
    <div class="modal fade" id="modalTambahGuru" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('admin.data-guru.store') }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Tambah Guru Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">NIP / NUPTK</label><input type="text" name="nip" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Nama Lengkap (Beserta Gelar)</label><input type="text" name="name" class="form-control" required></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jenis Kelamin</label>
                                <select name="gender" class="form-select" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mata Pelajaran</label>
                                <select name="subject_ids[]" class="form-select" multiple>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">No. WhatsApp</label><input type="text" name="phone" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" class="form-control"></div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Akun login guru (username = NIP) akan dibuat otomatis dengan password acak yang ditampilkan setelah data disimpan.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Guru -->
    <div class="modal fade" id="modalEditGuru" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formEditGuru" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">NIP / NUPTK</label><input type="text" name="nip" id="editGuruNip" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Nama Lengkap (Beserta Gelar)</label><input type="text" name="name" id="editGuruName" class="form-control" required></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jenis Kelamin</label>
                                <select name="gender" id="editGuruGender" class="form-select" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mata Pelajaran</label>
                                <select name="subject_ids[]" id="editGuruSubjects" class="form-select" multiple>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">No. WhatsApp</label><input type="text" name="phone" id="editGuruPhone" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" id="editGuruEmail" class="form-control"></div>
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
        document.getElementById('modalEditGuru').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditGuru').action = button.dataset.action;
            document.getElementById('editGuruNip').value = button.dataset.nip;
            document.getElementById('editGuruName').value = button.dataset.name;
            document.getElementById('editGuruGender').value = button.dataset.gender;
            document.getElementById('editGuruPhone').value = button.dataset.phone ?? '';
            document.getElementById('editGuruEmail').value = button.dataset.email ?? '';

            const selectedIds = (button.dataset.subjectIds ?? '').split(',').filter(Boolean);
            const subjectSelect = document.getElementById('editGuruSubjects');
            Array.from(subjectSelect.options).forEach(option => {
                option.selected = selectedIds.includes(option.value);
            });
        });
    </script>
@endsection
