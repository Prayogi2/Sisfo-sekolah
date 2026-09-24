@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">{{ $quiz->title }}</h5><small class="text-muted">{{ $quiz->subject->name }} · {{ $quiz->duration_minutes }} menit</small></div>
            <div class="text-end"><span class="badge bg-danger fs-6" id="quizTimer">--:--</span><br><span class="badge bg-primary mt-1">{{ $attempt->status === 'in_progress' ? 'Sedang dikerjakan' : 'Selesai' }}</span></div>
        </div>
        <div class="card-body">
            <x-page-guide>Jawab semua soal sebelum waktu habis. Jawaban tersimpan begitu dipilih; kuis otomatis dikumpulkan jika waktu berakhir.</x-page-guide>
            @foreach($quiz->questions as $number => $question)
                @php $savedAnswer = optional($attempt->answers->firstWhere('quiz_question_id', $question->id))->answer ?? []; @endphp
                <form method="POST" action="{{ route('siswa.kuis.answer', $attempt) }}" class="border rounded p-3 mb-3">
                    @csrf
                    <input type="hidden" name="quiz_question_id" value="{{ $question->id }}">
                    <p class="fw-semibold">
                        {{ $number + 1 }}. {{ $question->question }}
                        @if($question->type === 'multiple')
                            <span class="badge bg-info text-dark">Pilih semua jawaban yang benar</span>
                        @endif
                    </p>
                    @if($question->media_path)
                        @if($question->media_type === 'video')
                            <video src="{{ Storage::url($question->media_path) }}" class="img-fluid rounded mb-3" controls></video>
                        @else
                            <img src="{{ Storage::url($question->media_path) }}" class="img-fluid rounded mb-3" alt="Media soal">
                        @endif
                    @endif
                    @foreach($question->options as $key => $option)
                        <label class="d-block mb-2"><input type="{{ $question->type === 'multiple' ? 'checkbox' : 'radio' }}" name="answer[]" value="{{ $key }}" {{ in_array($key, $savedAnswer, true) ? 'checked' : '' }}> {{ $key }}. {{ $option }}</label>
                    @endforeach
                    <button class="btn btn-sm btn-outline-primary">Simpan Jawaban</button>
                </form>
            @endforeach
            <form id="submitQuizForm" method="POST" action="{{ route('siswa.kuis.submit', $attempt) }}">@csrf<button class="btn btn-primary">Kumpulkan Kuis</button></form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const quizDeadline = {{ $attempt->started_at->copy()->addMinutes($quiz->duration_minutes)->timestamp * 1000 }};
    const quizTimer = document.getElementById('quizTimer');
    const submitQuizForm = document.getElementById('submitQuizForm');
    let timerInterval;
    const updateQuizTimer = () => {
        const remaining = Math.max(0, quizDeadline - Date.now());
        const seconds = Math.floor(remaining / 1000);
        quizTimer.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        if (remaining <= 0) {
            clearInterval(timerInterval);
            submitQuizForm.submit();
        }
    };
    updateQuizTimer();
    timerInterval = setInterval(updateQuizTimer, 1000);
</script>
@endpush
