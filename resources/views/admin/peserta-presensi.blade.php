@extends('layouts.app')

@section('title', 'Peserta Presensi')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Peserta Presensi</h1>
                <p class="text-muted mb-0">Data ringkas siswa yang dipakai untuk presensi scan QR.</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPeserta">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Peserta
            </button>
        </div>

        <x-page-guide>Halaman ini khusus data untuk presensi. Datanya <strong>sama</strong> dengan Data Siswa & Buku Induk (tidak disimpan dobel), jadi perubahan di sini langsung berlaku di sana juga. <strong>Wali kelas</strong> & <strong>tahun pelajaran</strong> mengikuti kelas yang dipilih — ubah lewat menu Pembagian Kelas. Peserta baru otomatis mendapat QR & akun login siswa.</x-page-guide>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.peserta-presensi') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Cari</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Nama, NISN, atau NIS...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Kelas</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((int) request('classroom_id') === $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button></div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Daftar Peserta Presensi</h6>
                <span class="badge bg-primary-subtle text-primary">{{ $students->total() }} siswa</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Wali Kelas</th>
                                <th>Tahun Pelajaran</th>
                                <th>ID / QR Siswa</th>
                                <th>NISN / NIS</th>
                                <th>Alamat</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td>{{ $students->firstItem() + $loop->index }}</td>
                                    <td class="fw-semibold">{{ $student->name }}</td>
                                    <td>{{ $student->classroom?->name ?? '-' }}</td>
                                    <td>{{ $student->classroom?->homeroomTeacher?->name ?? '-' }}</td>
                                    <td>{{ $student->classroom?->academic_year ?? '-' }}</td>
                                    <td>
                                        @if ($student->qr_token)
                                            <code class="small" title="{{ $student->qr_token }}">{{ \Illuminate\Support\Str::limit($student->qr_token, 10, '…') }}</code>
                                        @else
                                            <span class="text-muted small">Belum ada</span>
                                        @endif
                                    </td>
                                    <td><div>{{ $student->nisn ?: '-' }}</div><small class="text-muted">{{ $student->nis ?: '-' }}</small></td>
                                    <td class="small" style="max-width: 220px;">{{ $student->address ?: '-' }}</td>
                                    <td><span class="badge {{ $student->status === \App\Enums\StudentStatus::Active ? 'bg-success' : 'bg-secondary' }}">{{ $student->status->label() }}</span></td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('admin.data-siswa.kartu-qr', $student) }}" target="_blank" class="btn btn-sm btn-light" title="Cetak Kartu QR"><i class="bi bi-qr-code text-success"></i></a>
                                        <button type="button" class="btn btn-sm btn-light" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEditPeserta"
                                            data-action="{{ route('admin.data-siswa.update', $student) }}"
                                            data-name="{{ $student->name }}"
                                            data-nisn="{{ $student->nisn }}"
                                            data-nis="{{ $student->nis }}"
                                            data-gender="{{ $student->gender?->value }}"
                                            data-classroom-id="{{ $student->classroom_id }}"
                                            data-address="{{ $student->address }}"
                                            data-status="{{ $student->status->value }}"
                                        ><i class="bi bi-pencil-square text-warning"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada peserta yang cocok dengan filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($students->hasPages())
                <div class="card-footer bg-white">{{ $students->links() }}</div>
            @endif
        </div>
    </div>

    @foreach ([['id' => 'modalTambahPeserta', 'title' => 'Tambah Peserta Presensi', 'color' => 'primary', 'icon' => 'bi-person-plus', 'action' => route('admin.peserta-presensi.store'), 'method' => 'POST', 'prefix' => 'tambah'], ['id' => 'modalEditPeserta', 'title' => 'Edit Peserta Presensi', 'color' => 'warning', 'icon' => 'bi-pencil-square', 'action' => '', 'method' => 'PUT', 'prefix' => 'edit']] as $modal)
        <div class="modal fade" id="{{ $modal['id'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="{{ $modal['action'] }}" id="{{ $modal['prefix'] }}PesertaForm">
                    @csrf
                    @if ($modal['method'] === 'PUT') @method('PUT') @endif
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-{{ $modal['color'] }} {{ $modal['color'] === 'primary' ? 'text-white' : 'text-dark' }}">
                            <h5 class="modal-title fw-bold"><i class="bi {{ $modal['icon'] }} me-2"></i>{{ $modal['title'] }}</h5>
                            <button type="button" class="btn-close {{ $modal['color'] === 'primary' ? 'btn-close-white' : '' }}" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-8"><label class="form-label fw-semibold">1) Nama <span class="text-danger">*</span></label><input type="text" name="name" id="{{ $modal['prefix'] }}Name" class="form-control" required></div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="gender" id="{{ $modal['prefix'] }}Gender" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">2) Kelas</label>
                                    <select name="classroom_id" id="{{ $modal['prefix'] }}ClassroomId" class="form-select classroom-select" data-info-target="{{ $modal['prefix'] }}ClassroomInfo">
                                        <option value="">-- Belum Ada Kelas --</option>
                                        @foreach ($classrooms as $classroom)
                                            <option value="{{ $classroom->id }}" data-homeroom="{{ $classroom->homeroomTeacher?->name ?? 'Belum ditetapkan' }}" data-year="{{ $classroom->academic_year }}">{{ $classroom->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">3) Wali Kelas &amp; 4) Tahun Pelajaran</label>
                                    <div class="form-control bg-light text-muted" id="{{ $modal['prefix'] }}ClassroomInfo">Pilih kelas untuk melihat wali kelas &amp; tahun pelajaran.</div>
                                </div>
                                <div class="col-md-4"><label class="form-label fw-semibold">6) NISN <span class="text-danger">*</span></label><input type="text" name="nisn" id="{{ $modal['prefix'] }}Nisn" class="form-control" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" title="NISN harus 10 digit angka" required></div>
                                <div class="col-md-4"><label class="form-label fw-semibold">NIS <span class="text-danger">*</span></label><input type="text" name="nis" id="{{ $modal['prefix'] }}Nis" class="form-control" required></div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">8) Status Siswa</label>
                                    <select name="status" id="{{ $modal['prefix'] }}Status" class="form-select">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12"><label class="form-label fw-semibold">7) Alamat Siswa</label><textarea name="address" id="{{ $modal['prefix'] }}Address" class="form-control" rows="2"></textarea></div>
                            </div>
                            <p class="text-muted small mt-3 mb-0"><i class="bi bi-qr-code me-1"></i>5) ID/QR siswa {{ $modal['prefix'] === 'tambah' ? 'dibuat otomatis saat peserta disimpan' : 'tidak berubah' }} — cetak lewat tombol QR di tabel.</p>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-{{ $modal['color'] }}">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        (function () {
            function showClassroomInfo(select) {
                const option = select.selectedOptions[0];
                document.getElementById(select.dataset.infoTarget).textContent = option && option.value
                    ? `Wali kelas: ${option.dataset.homeroom} · Tahun pelajaran: ${option.dataset.year}`
                    : 'Pilih kelas untuk melihat wali kelas & tahun pelajaran.';
            }

            document.querySelectorAll('.classroom-select').forEach((select) => {
                select.addEventListener('change', () => showClassroomInfo(select));
            });

            document.getElementById('modalEditPeserta').addEventListener('show.bs.modal', function (event) {
                const data = event.relatedTarget.dataset;
                document.getElementById('editPesertaForm').action = data.action;
                document.getElementById('editName').value = data.name;
                document.getElementById('editNisn').value = data.nisn;
                document.getElementById('editNis').value = data.nis;
                document.getElementById('editGender').value = data.gender;
                document.getElementById('editClassroomId').value = data.classroomId || '';
                document.getElementById('editAddress').value = data.address || '';
                document.getElementById('editStatus').value = data.status;
                showClassroomInfo(document.getElementById('editClassroomId'));
            });
        })();
    </script>
@endsection
