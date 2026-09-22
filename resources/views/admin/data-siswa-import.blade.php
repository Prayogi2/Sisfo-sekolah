@extends('layouts.app')

@section('title', 'Import Data Siswa')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold">Import Data Siswa dari Excel</h1>
                <p class="text-muted mb-0">Tambahkan banyak siswa sekaligus dari satu file. Data Buku Induk lainnya tetap bisa dilengkapi menyusul per siswa.</p>
            </div>
            <a href="{{ route('admin.data-siswa') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
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

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-upload me-2"></i>Unggah File</h6></div>
                    <div class="card-body">
                        <form action="{{ route('admin.data-siswa.import.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">File Excel / CSV</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <small class="text-muted">Format XLSX, XLS, atau CSV. Maksimal 5 MB, 1000 baris data.</small>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Impor Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Cara Mengisi</h6>
                        <ol class="small mb-3 ps-3">
                            <li>Unduh template di bawah, jangan ubah nama kolomnya.</li>
                            <li>Kolom <strong>NISN, NIS, Nama Lengkap, Jenis Kelamin</strong> wajib diisi. Jenis Kelamin diisi L atau P.</li>
                            <li>Kolom <strong>Kelas</strong> diisi nama kelas persis seperti di menu Pembagian Kelas (mis. "1-A"). Boleh dikosongkan jika belum ada kelasnya.</li>
                            <li>Kolom lainnya opsional dan bisa dilengkapi nanti lewat Buku Induk.</li>
                            <li>NISN & NIS harus unik — baris dengan NISN/NIS yang sudah terdaftar akan gagal dan dilaporkan, baris lain tetap diproses.</li>
                            <li>Akun login siswa dibuat otomatis (username = NISN, password default <code>password123</code>).</li>
                        </ol>
                        <a href="{{ route('admin.data-siswa.import.template') }}" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-download me-1"></i>Unduh Template Excel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
