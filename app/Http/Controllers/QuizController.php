<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizController extends Controller
{
    use ResolvesCurrentStudent;

    public function questionBank(Request $request): View
    {
        abort_unless($request->user()->hasRole(['admin', 'guru']), 403);

        $subjects = Subject::query()
            ->when($request->user()->hasRole('guru'), fn ($query) => $query->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id)))
            ->orderBy('name')->get();
        $questions = QuizQuestion::with('subject')->when($request->user()->hasRole('guru'), fn ($query) => $query->where('created_by', $request->user()->id))->latest()->get();
        $quizzes = Quiz::with(['subject', 'classroom'])->when($request->user()->hasRole('guru'), fn ($query) => $query->where('created_by', $request->user()->id))->latest()->get();

        return view($request->user()->hasRole('admin') ? 'admin.bank-soal' : 'guru.bank-soal', [
            'subjects' => $subjects,
            'classrooms' => Classroom::orderBy('name')->get(),
            'questions' => $questions,
            'quizzes' => $quizzes,
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string', 'max:10000'],
            'options' => ['required', 'array:A,B,C,D'],
            'options.A' => ['required', 'string', 'max:1000'],
            'options.B' => ['required', 'string', 'max:1000'],
            'options.C' => ['required', 'string', 'max:1000'],
            'options.D' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $data['created_by'] = $request->user()->id;
        $data['points'] ??= 1;
        QuizQuestion::create($data);

        return back()->with('success', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function updateQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string', 'max:10000'],
            'options' => ['required', 'array:A,B,C,D'],
            'options.A' => ['required', 'string', 'max:1000'], 'options.B' => ['required', 'string', 'max:1000'],
            'options.C' => ['required', 'string', 'max:1000'], 'options.D' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'in:A,B,C,D'], 'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'], 'is_active' => ['sometimes', 'boolean'],
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $question->update($data);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        abort_if($question->quizzes()->whereHas('attempts')->exists(), 422, 'Soal yang sudah dipakai dalam kuis tidak dapat dihapus.');
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function storeQuiz(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'], 'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_published' => ['sometimes', 'boolean'], 'question_ids' => ['required', 'array', 'min:1'], 'question_ids.*' => ['integer', 'exists:quiz_questions,id'],
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $questionIds = $data['question_ids'];
        $validQuestionIds = QuizQuestion::where('subject_id', $data['subject_id'])->whereIn('id', $questionIds)->pluck('id')->all();
        abort_if(count($validQuestionIds) !== count($questionIds), 422, 'Semua soal harus berasal dari mata pelajaran kuis.');

        $quizData = collect($data)->except('question_ids')->all();
        $quizData['created_by'] = $request->user()->id;
        $quiz = Quiz::create($quizData);
        $questionPoints = QuizQuestion::whereIn('id', $questionIds)->pluck('points', 'id');
        $quiz->questions()->attach(collect($questionIds)->values()->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index + 1, 'points' => $questionPoints[$id]]])->all());

        return back()->with('success', 'Kuis CBT berhasil dibuat.');
    }

    public function available(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) return $student;

        $quizzes = Quiz::with(['subject', 'creator.teacher', 'attempts' => fn ($query) => $query->where('student_id', $student->id)])
            ->where('classroom_id', $student->classroom_id)->where('is_published', true)->latest()->get();

        return view('siswa.kuis-ranking', compact('student', 'quizzes'));
    }

    public function start(Request $request, Quiz $quiz, CurrentStudentResolver $resolver): RedirectResponse|View
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) return $student;
        abort_unless($quiz->classroom_id === $student->classroom_id && $quiz->isAvailable(), 403);
        $attempt = QuizAttempt::firstOrCreate(['quiz_id' => $quiz->id, 'student_id' => $student->id], ['started_at' => now(), 'total_questions' => $quiz->questions()->count()]);
        abort_if($attempt->status === 'submitted', 422, 'Kuis sudah dikumpulkan.');
        $attempt->load('answers');
        $quiz->load('questions');

        return view('siswa.kuis', compact('quiz', 'attempt'));
    }

    public function answer(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $data = $request->validate(['quiz_question_id' => ['required', 'integer', 'exists:quiz_questions,id'], 'answer' => ['nullable', 'in:A,B,C,D']]);
        $this->assertAttemptOwner($request, $attempt);
        $question = $attempt->quiz->questions()->whereKey($data['quiz_question_id'])->firstOrFail();
        abort_if($attempt->status === 'submitted' || ($attempt->started_at && now()->greaterThan($attempt->started_at->copy()->addMinutes($attempt->quiz->duration_minutes))), 422, 'Waktu pengerjaan kuis sudah habis.');
        $answer = $data['answer'] ?? null;
        $isCorrect = $answer !== null && $answer === $question->correct_answer;
        QuizAnswer::updateOrCreate(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id], ['answer' => $answer, 'is_correct' => $isCorrect, 'awarded_points' => $isCorrect ? $question->pivot->points : 0]);

        return back()->with('success', 'Jawaban tersimpan.');
    }

    public function submit(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $this->assertAttemptOwner($request, $attempt);
        abort_if($attempt->status === 'submitted', 422, 'Kuis sudah dikumpulkan.');
        $questions = $attempt->quiz->questions()->get();
        $answers = $attempt->answers()->get()->keyBy('quiz_question_id');
        $totalPoints = $questions->sum(fn ($question) => $question->pivot->points);
        $earnedPoints = $answers->sum('awarded_points');
        DB::transaction(fn () => $attempt->update(['status' => 'submitted', 'submitted_at' => now(), 'score' => $totalPoints ? round($earnedPoints / $totalPoints * 100, 2) : 0, 'correct_answers' => $answers->where('is_correct', true)->count(), 'total_questions' => $questions->count()]));

        return redirect()->route('siswa.kuis')->with('success', 'Kuis berhasil dikumpulkan.');
    }

    public function results(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) return $student;
        $attempts = QuizAttempt::with(['quiz.subject', 'quiz.classroom'])->where('student_id', $student->id)->where('status', 'submitted')->latest('submitted_at')->get();

        return view('wali-murid.hasil-kuis', compact('student', 'attempts'));
    }

    private function authorizeSubject(Request $request, int $subjectId): void
    {
        abort_unless($request->user()->hasRole('admin') || Subject::whereKey($subjectId)->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id))->exists(), 403);
    }

    private function authorizeQuestion(Request $request, QuizQuestion $question): void
    {
        abort_unless($request->user()->hasRole('admin') || $question->created_by === $request->user()->id, 403);
    }

    private function assertAttemptOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($request->user()->guardian()->whereHas('students', fn ($query) => $query->whereKey($attempt->student_id))->exists(), 403);
    }
}
