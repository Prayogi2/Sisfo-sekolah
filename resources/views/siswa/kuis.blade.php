@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">{{ $quiz->title }}</h5><small class="text-muted">{{ $quiz->subject->name }} · {{ $quiz->duration_minutes }} menit</small></div>
            <span class="badge bg-primary">{{ $attempt->status === 'in_progress' ? 'Sedang dikerjakan' : 'Selesai' }}</span>
        </div>
        <div class="card-body">
            @foreach($quiz->questions as $number => $question)
                <form method="POST" action="{{ route('siswa.kuis.answer', $attempt) }}" class="border rounded p-3 mb-3">
                    @csrf
                    <input type="hidden" name="quiz_question_id" value="{{ $question->id }}">
                    <p class="fw-semibold">{{ $number + 1 }}. {{ $question->question }}</p>
                    @foreach($question->options as $key => $option)
                        <label class="d-block mb-2"><input type="radio" name="answer" value="{{ $key }}" {{ optional($attempt->answers->firstWhere('quiz_question_id', $question->id))->answer === $key ? 'checked' : '' }}> {{ $key }}. {{ $option }}</label>
                    @endforeach
                    <button class="btn btn-sm btn-outline-primary">Simpan Jawaban</button>
                </form>
            @endforeach
            <form method="POST" action="{{ route('siswa.kuis.submit', $attempt) }}">@csrf<button class="btn btn-primary">Kumpulkan Kuis</button></form>
        </div>
    </div>
</div>
@endsection
