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
                                <th class="text-center">Jumlah Guru Pengampu</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $subject)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-primary-soft text-primary" style="background-color: #e7f1ff;">{{ $subject->code }}</span></td>
                                    <td class="fw-semibold text-dark">{{ $subject->name }}</td>
                                    <td class="text-center">{{ $subject->teachers_count }}</td>
                                    <td class="text-center">
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
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada mata pelajaran. Tambahkan mapel baru untuk mulai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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

    <script>
        document.getElementById('modalEditMapel').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditMapel').action = button.dataset.action;
            document.getElementById('editMapelCode').value = button.dataset.code;
            document.getElementById('editMapelName').value = button.dataset.name;
        });
    </script>
@endsection
