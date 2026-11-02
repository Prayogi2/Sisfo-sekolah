@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="mb-0">{{ $quiz->title }}</h5><small class="text-muted">{{ $quiz->subject->name }} · {{ $quiz->duration_minutes }} menit</small></div>
            <div class="text-end"><span class="badge bg-danger fs-6" id="quizTimer">--:--</span><br><span class="badge bg-primary mt-1">{{ $attempt->status === 'in_progress' ? 'Sedang dikerjakan' : 'Selesai' }}</span></div>
        </div>
        <div class="card-body">
            <x-page-guide>Kuis dapat berisi pilihan ganda (pilih satu), pilihan ganda kompleks (pilih semua yang benar), essay (ketik jawaban), atau menjodohkan. Jawaban tersimpan saat disimpan; kuis otomatis dikumpulkan jika waktu habis.</x-page-guide>
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
                    @if($question->type === \App\Models\QuizQuestion::TYPE_ESSAY)
                        <textarea name="answer" rows="4" maxlength="5000" class="form-control mb-2" placeholder="Tulis jawabanmu di sini...">{{ $savedAnswer['text'] ?? '' }}</textarea>
                    @elseif($question->type === \App\Models\QuizQuestion::TYPE_MATCHING)
                        @foreach($question->matchingPairs() as $pairIndex => $pair)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-5 fw-semibold">{{ $pair['left'] }}</div>
                                <div class="col-md-7">
                                    <select name="answer[{{ $pairIndex }}]" class="form-select form-select-sm">
                                        <option value="">— Pilih pasangan —</option>
                                        @foreach($question->shuffledMatchingChoices() as $choice)
                                            <option value="{{ $choice }}" @selected(($savedAnswer[$pairIndex] ?? null) === $choice)>{{ $choice }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach($question->options as $key => $option)
                            <label class="quiz-option">
                                <input type="{{ $question->type === 'multiple' ? 'checkbox' : 'radio' }}" name="answer[]" value="{{ $key }}" {{ in_array($key, $savedAnswer, true) ? 'checked' : '' }} class="form-check-input mt-0">
                                <span><strong>{{ $key }}.</strong> {{ $option }}</span>
                            </label>
                        @endforeach
                    @endif
                    <button class="btn btn-sm btn-outline-primary">Simpan Jawaban</button>
                </form>
            @endforeach
            <form id="submitQuizForm" method="POST" action="{{ route('siswa.kuis.submit', $attempt) }}">@csrf<button class="btn btn-primary">Kumpulkan Kuis</button></form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Pilihan jawaban dibuat sebagai baris penuh agar nyaman disentuh di HP. */
.quiz-option {
    display: flex; gap: 10px; align-items: flex-start;
    border: 1px solid #dee2e6; border-radius: 10px;
    padding: 12px 14px; margin-bottom: 8px; cursor: pointer;
}
.quiz-option input { width: 1.15rem; height: 1.15rem; flex: none; margin-top: .15rem; }
.quiz-option:has(input:checked) { border-color: #0d6efd; background: #eef4ff; }

@media (max-width: 575.98px) {
    .quiz-option { padding: 10px 12px; }
    /* Sisa waktu tetap terlihat walau soal digulir jauh ke bawah. */
    #quizTimer { position: sticky; top: 70px; }
}
</style>
@endpush

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
