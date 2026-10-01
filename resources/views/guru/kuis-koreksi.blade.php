@extends('layouts.app')

@section('title', 'Koreksi Essay - '.$quiz->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Koreksi Essay: {{ $quiz->title }}</h1>
            <p class="text-muted mb-0">{{ $quiz->subject->name }} · Kelas {{ $quiz->classroom->name }}</p>
        </div>
        <a href="{{ route('guru.bank-soal') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <x-page-guide>Baca jawaban siswa, isi nilai (0 sampai poin maksimal soal), lalu klik <strong>Simpan Nilai</strong>. Nilai kuis dan nilai kuis di rapor siswa otomatis dihitung ulang. Kolom yang dikosongkan tidak diubah.</x-page-guide>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($essayQuestions->isEmpty())
        <div class="alert alert-light border text-muted">Kuis ini tidak memiliki soal essay.</div>
    @else
        <form action="{{ route('guru.kuis.koreksi.simpan', $quiz) }}" method="POST">
            @csrf
            @method('PUT')
            @foreach($essayQuestions as $question)
                @php
                    $questionAnswers = $answers->get($question->id, collect());
                    $maxPoints = (int) $question->pivot->points;
                @endphp
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-bold">{{ $question->question }}</div>
                            <span class="badge bg-primary align-self-start">Maks {{ $maxPoints }} poin</span>
                        </div>
                        @if(filled($question->correct_answer[0] ?? null))
                            <div class="small text-success mt-1"><i class="bi bi-key me-1"></i>Pedoman: {{ $question->correct_answer[0] }}</div>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light"><tr><th style="width: 22%;">Siswa</th><th>Jawaban</th><th style="width: 150px;">Nilai</th></tr></thead>
                                <tbody>
                                    @forelse($questionAnswers as $answer)
                                        <tr>
                                            <td class="fw-semibold">{{ $answer->attempt->student->name }}</td>
                                            <td style="white-space: pre-line;">{{ $answer->answer['text'] ?? '' }}@if(blank($answer->answer['text'] ?? null))<span class="text-muted fst-italic">Tidak menjawab</span>@endif</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="scores[{{ $answer->id }}]" value="{{ old("scores.{$answer->id}", $answer->graded_at ? $answer->awarded_points : '') }}" min="0" max="{{ $maxPoints }}" class="form-control" placeholder="0–{{ $maxPoints }}" aria-label="Nilai {{ $answer->attempt->student->name }}">
                                                    <span class="input-group-text">/ {{ $maxPoints }}</span>
                                                </div>
                                                @if($answer->graded_at)
                                                    <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i>Sudah dinilai</div>
                                                @else
                                                    <div class="small text-warning mt-1">Belum dinilai</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada siswa yang menjawab soal ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Nilai</button>
            </div>
        </form>
    @endif
</div>
@endsection
