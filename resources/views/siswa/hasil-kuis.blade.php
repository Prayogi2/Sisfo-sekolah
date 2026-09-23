@extends('layouts.app')

@section('title', 'Hasil Kuis')

@section('content')
<div class="container-fluid">
    <x-page-guide>Daftar kuis yang sudah kamu kumpulkan beserta nilainya. Kuis yang belum dikerjakan belum muncul di sini.</x-page-guide>
    <div class="card shadow-sm"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Hasil Kuis {{ $student->name }}</h5><a href="{{ route('siswa.kuis') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-trophy me-1"></i>Peringkat Kelas</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Kuis</th><th>Mapel</th><th>Nilai</th><th>Dikumpulkan</th></tr></thead><tbody>
            @forelse($attempts as $attempt)<tr><td>{{ $attempt->quiz->title }}</td><td>{{ $attempt->quiz->subject->name }}</td><td><span class="badge bg-success">{{ $attempt->score }}</span></td><td>{{ $attempt->submitted_at?->format('d/m/Y H:i') }}</td></tr>
            @empty<tr><td colspan="4" class="text-center py-4">Belum ada hasil kuis.</td></tr>@endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
