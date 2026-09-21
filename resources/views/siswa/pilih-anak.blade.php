@extends('layouts.app')

@section('title', 'Pilih Anak')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800 fw-bold">Pilih Anak</h1>
        <p class="text-muted">Akun Anda terhubung ke lebih dari satu anak. Pilih salah satu untuk melanjutkan.</p>

        <div class="row">
            @foreach ($children as $child)
                <div class="col-md-4 mb-4">
                    <form action="{{ route('pilih-anak.select', $child) }}" method="GET">
                        <button type="submit" class="card border-0 shadow-sm w-100 text-start p-3" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($child->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-3" width="50" height="50" alt="">
                                <div>
                                    <div class="fw-bold text-dark">{{ $child->name }}</div>
                                    <small class="text-muted">Kelas {{ $child->classroom?->name ?? '-' }}</small>
                                </div>
                            </div>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection
