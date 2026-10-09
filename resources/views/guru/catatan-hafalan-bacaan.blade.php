@extends('layouts.app')

@section('title', 'Catatan Hafalan & Bacaan')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Catatan Hafalan & Bacaan</h1>
            <p class="text-muted mb-0">Catat perkembangan hafalan dan bacaan siswa di kelas yang Anda ampu atau walikan.</p>
        </div>
        @if($classrooms->count() > 1)
            <form method="GET" action="{{ route('guru.catatan-hafalan-bacaan') }}">
                <select name="classroom" class="form-select fw-semibold" onchange="this.form.submit()" aria-label="Pilih kelas">
                    @foreach($classrooms as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $classroom?->id)>Kelas {{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if(! $classroom)
        <div class="alert alert-light border text-muted"><i class="bi bi-info-circle me-1"></i>Belum ada kelas yang ditugaskan kepada Anda sebagai wali kelas.</div>
    @else
        <x-page-guide>Pilih jenis catatan, isi surah atau materi/halaman, lalu tuliskan capaian siswa dan tanggal pencatatan.</x-page-guide>
        <p class="small text-muted"><i class="bi bi-door-open me-1"></i>Kelas <strong class="text-dark">{{ $classroom->name }}</strong></p>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Tambah Catatan</div>
            <div class="card-body">
                <form action="{{ route('guru.catatan-hafalan-bacaan.store', $classroom) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="recitationStudent" class="form-label">Siswa</label>
                            <select id="recitationStudent" name="student_id" class="form-select" required>
                                <option value="">Pilih siswa</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recitationType" class="form-label">Jenis Catatan</label>
                            <select id="recitationType" name="type" class="form-select" required>
                                <option value="memorization" @selected(old('type') === 'memorization')>Hafalan</option>
                                <option value="reading" @selected(old('type') === 'reading')>Bacaan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recitationMaterial" class="form-label">Surah / Materi / Halaman</label>
                            <input id="recitationMaterial" name="material" value="{{ old('material') }}" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="recitationAchievement" class="form-label">Capaian</label>
                            <input id="recitationAchievement" name="achievement" value="{{ old('achievement') }}" maxlength="255" class="form-control" placeholder="mis. Ayat 1-10 lancar" required>
                        </div>
                        <div class="col-md-6">
                            <label for="recitationDate" class="form-label">Tanggal</label>
                            <input id="recitationDate" type="date" name="recorded_at" value="{{ old('recorded_at', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="recitationNotes" class="form-label">Catatan Tambahan</label>
                            <textarea id="recitationNotes" name="notes" rows="2" maxlength="2000" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan Catatan</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Riwayat Catatan</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Siswa</th><th>Jenis</th><th>Surah / Materi / Halaman</th><th>Capaian</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse($notes as $note)
                            <tr>
                                <td>{{ $note->recorded_at->format('d/m/Y') }}</td>
                                <td>{{ $note->student->name }}</td>
                                <td>{{ $note->type === 'memorization' ? 'Hafalan' : 'Bacaan' }}</td>
                                <td>{{ $note->material }}</td>
                                <td>{{ $note->achievement }}</td>
                                <td>{{ $note->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada catatan untuk kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($notes->hasPages())
                <div class="card-footer bg-white">{{ $notes->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
