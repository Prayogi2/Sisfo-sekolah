@extends('layouts.app')

@section('title', 'Kritik & Saran')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Kritik & Saran dari Wali Murid</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Daftar Pesan</h6></div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse ($feedbacks as $feedback)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-dark">{{ $feedback->guardian->name }}</span>
                            <small class="text-muted">{{ $feedback->created_at->translatedFormat('d F Y, H:i') }} WIB</small>
                        </div>
                        <p class="mb-0 mt-1">{{ $feedback->message }}</p>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada kritik/saran yang masuk.</div>
                @endforelse
            </div>
        </div>
        @if ($feedbacks->hasPages())
            <div class="card-footer bg-white">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
