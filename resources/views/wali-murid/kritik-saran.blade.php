@extends('layouts.app')

@section('title', 'Kritik & Saran')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Kritik & Saran</h1>
    </div>

    <x-page-guide>Kirim pesan lewat form di samping — langsung masuk sebagai notifikasi ke admin.</x-page-guide>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-chat-square-text me-2"></i>Kirim Kritik/Saran ke Admin</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('wali.kritik-saran.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Sampaikan kritik atau saran Anda..." required>{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Kirim</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Riwayat Pesan Anda</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($feedbacks as $feedback)
                            <div class="list-group-item">
                                <p class="mb-1">{{ $feedback->message }}</p>
                                <small class="text-muted">{{ $feedback->created_at->translatedFormat('d F Y, H:i') }} WIB</small>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">Belum ada pesan yang dikirim.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
