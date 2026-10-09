@extends('layouts.app')

@section('title', 'Kritik & Saran')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold">Kritik & Saran</h1>
        <p class="text-muted mb-0">Sampaikan masukan kepada admin sekolah. Identitas pengirim hanya terlihat oleh admin.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold"><i class="bi bi-chat-square-text me-2 text-primary"></i>Kirim Pesan</div>
        <div class="card-body">
            <form action="{{ route('siswa.kritik-saran.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="feedbackMessage" class="form-label">Kritik atau saran</label>
                    <textarea id="feedbackMessage" name="message" rows="5" maxlength="5000" class="form-control" required>{{ old('message') }}</textarea>
                    <div class="form-text">Pesan maksimal 5.000 karakter.</div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim ke Admin</button>
            </form>
        </div>
    </div>
</div>
@endsection
