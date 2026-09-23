@extends('layouts.app')

@section('title', $announcement->title)

@section('content')
<div class="container-fluid">
    <a href="{{ route('siswa.notifikasi') }}" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-1"></i> Semua notifikasi</a>

    <div class="card shadow-sm" style="max-width: 760px;">
        <div class="card-body p-4">
            <span class="badge bg-{{ $announcement->category->color() }} mb-3"><i class="bi {{ $announcement->category->icon() }} me-1"></i>{{ $announcement->category->label() }}</span>
            <h1 class="h4 fw-bold mb-1">{{ $announcement->title }}</h1>
            <p class="text-muted small mb-4">
                {{ $announcement->published_at->translatedFormat('l, d F Y · H:i') }} WIB
                ({{ $announcement->published_at->locale('id')->diffForHumans() }})
            </p>
            <div class="fs-6" style="white-space: pre-line;">{{ $announcement->message }}</div>
        </div>
    </div>
</div>
@endsection
