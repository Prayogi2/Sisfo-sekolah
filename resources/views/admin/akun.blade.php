@extends('layouts.app')

@section('title', 'Kelola Akun Login')

@section('content')
<div class="container-fluid">
    <div class="mb-4"><h1 class="h3 mb-1 fw-bold">Kelola Akun Login</h1><p class="text-muted mb-0">Reset password guru atau siswa.</p></div>
    <x-page-guide>Cari akun di tabel, lalu klik <strong>Reset Password</strong> untuk membuatkan password baru. Password baru hanya ditampilkan sekali — segera catat dan sampaikan ke pemiliknya.</x-page-guide>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Nama</th><th>Role</th><th>Email</th><th>ID Login</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        @forelse($users as $user)
            <tr><td class="fw-semibold">{{ $user->name }}</td><td>{{ ucfirst($user->role) }}</td><td>{{ $user->email }}</td><td>{{ $user->role === 'siswa' ? $user->name : $user->email }}</td><td class="text-end">
                <form method="POST" action="{{ route('admin.akun.reset-password', $user) }}" onsubmit="return confirm('Reset password akun ini?')">@csrf<button class="btn btn-sm btn-outline-warning"><i class="bi bi-key me-1"></i>Reset Password</button></form>
            </td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada akun.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
</div>
@endsection
