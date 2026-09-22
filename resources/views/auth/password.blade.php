@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')
<div class="container-fluid"><div class="row justify-content-center"><div class="col-lg-6">
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h1 class="h4 fw-bold mb-1">Ganti Password</h1>
        <p class="text-muted mb-4">Masukkan password saat ini untuk membuat password baru.</p>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('account.password.update') }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Password saat ini</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password baru</label><input type="password" name="password" class="form-control" minlength="8" required></div>
            <div class="mb-4"><label class="form-label">Konfirmasi password baru</label><input type="password" name="password_confirmation" class="form-control" minlength="8" required></div>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan Password</button>
        </form>
    </div></div>
</div></div></div>
@endsection
