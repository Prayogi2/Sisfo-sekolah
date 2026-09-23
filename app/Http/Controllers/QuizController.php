<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\CurrentStudentResolver;
use App\Services\QuizAttemptFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuizController extends Controller
{
    use ResolvesCurrentStudent;

    public function questionBank(Request $request): View
    {
        abort_unless($request->user()->hasRole(['admin', 'guru']), 403);

        $isGuru = $request->user()->hasRole('guru');
        $teacher = $isGuru ? $this->teacherFor($request) : null;

        $subjects = Subject::query()
            ->when(
                $isGuru,
                fn ($query) => $query->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id))
            )
            ->orderBy('name')
            ->get();
        $questions = QuizQuestion::with('subject')->when($isGuru, fn ($query) => $query->where('created_by', $request->user()->id))->latest()->get();
        $quizzes = Quiz::with(['subject', 'classroom', 'questions'])->when($isGuru, fn ($query) => $query->where('created_by', $request->user()->id))->latest()->get();

        // Kelas yang boleh dipilih guru saat membuat kuis: gabungan semua
        // kelas dari semua mapel yang ia ajarkan. Pasangan mapel+kelas yang
        // sebenarnya valid dikirim lewat $subjectClassroomMap untuk
        // menyaring pilihan kelas di formulir sesuai mapel yang dipilih.
        // Guru tanpa profil guru sama sekali tidak melihat kelas apa pun —
        // hanya admin yang unrestricted.
        $classrooms = match (true) {
            $teacher !== null => $teacher->classrooms()->orderBy('name')->get(),
            $isGuru => collect(),
            default => Classroom::orderBy('name')->get(),
        };
        $subjectClassroomMap = $teacher !== null
            ? $teacher->teachingAssignments->groupBy('subject_id')->map(fn ($rows) => $rows->pluck('classroom_id'))
            : collect();

        return view($request->user()->hasRole('admin') ? 'admin.bank-soal' : 'guru.bank-soal', [
            'subjects' => $subjects,
            'classrooms' => $classrooms,
            'subjectClassroomMap' => $subjectClassroomMap,
            'questions' => $questions,
            'quizzes' => $quizzes,
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string', 'max:10000'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:51200'],
            'options' => ['required', 'array:A,B,C,D'],
            'options.A' => ['required', 'string', 'max:1000'],
            'options.B' => ['required', 'string', 'max:1000'],
            'options.C' => ['required', 'string', 'max:1000'],
            'options.D' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'subject_id.required' => 'Pilih mata pelajaran untuk soal ini.',
            'correct_answer.required' => 'Pilih kunci jawaban.',
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $data['created_by'] = $request->user()->id;
        $data['points'] ??= 1;
        $this->storeQuestionMedia($request, $data);
        QuizQuestion::create($data);

        return back()->with('success', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function updateQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string', 'max:10000'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:51200'],
            'options' => ['required', 'array:A,B,C,D'],
            'options.A' => ['required', 'string', 'max:1000'], 'options.B' => ['required', 'string', 'max:1000'],
            'options.C' => ['required', 'string', 'max:1000'], 'options.D' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'in:A,B,C,D'], 'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'], 'is_active' => ['sometimes', 'boolean'],
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $removeMedia = $request->boolean('remove_media');
        unset($data['media'], $data['remove_media']);
        if ($request->hasFile('media')) {
            $this->deleteQuestionMedia($question);
            $data['media_path'] = $request->file('media')->store('quiz-media', 'public');
            $data['media_type'] = str_starts_with($request->file('media')->getMimeType(), 'video/') ? 'video' : 'image';
        } elseif ($removeMedia) {
            $this->deleteQuestionMedia($question);
            $data['media_path'] = null;
            $data['media_type'] = null;
        }
        $question->update($data);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        abort_if($question->quizzes()->whereHas('attempts')->exists(), 422, 'Soal yang sudah dipakai dalam kuis tidak dapat dihapus.');
        $this->deleteQuestionMedia($question);
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function storeQuiz(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'], 'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'], 'show_score_per_question' => ['sometimes', 'boolean'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_published' => ['sometimes', 'boolean'], 'question_ids' => ['required', 'array', 'min:1'], 'question_ids.*' => ['integer', 'exists:quiz_questions,id'],
        ], [
            'subject_id.required' => 'Pilih mata pelajaran untuk kuis ini.',
            'classroom_id.required' => 'Pilih kelas yang akan mengerjakan kuis ini.',
            'question_ids.required' => 'Pilih minimal satu soal dari bank soal untuk dimasukkan ke kuis ini.',
            'question_ids.min' => 'Pilih minimal satu soal dari bank soal untuk dimasukkan ke kuis ini.',
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $this->authorizeClassroom($request, (int) $data['subject_id'], (int) $data['classroom_id']);
        $questionIds = $data['question_ids'];
        $validQuestionIds = QuizQuestion::where('subject_id', $data['subject_id'])->whereIn('id', $questionIds)->pluck('id')->all();
        abort_if(count($validQuestionIds) !== count($questionIds), 422, 'Semua soal harus berasal dari mata pelajaran kuis.');

        $quizData = collect($data)->except('question_ids')->all();
        $quizData['created_by'] = $request->user()->id;
        // Semua kuis dibuat sebagai kuis Kahoot (serentak, dikendalikan guru).
        $quizData['mode'] = 'live';
        $quizData['show_score_per_question'] = (bool) ($quizData['show_score_per_question'] ?? false);
        $quizData['live_phase'] = 'lobby';
        $quiz = Quiz::create($quizData);
        $questionPoints = QuizQuestion::whereIn('id', $questionIds)->pluck('points', 'id');
        $quiz->questions()->attach(collect($questionIds)->values()->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index + 1, 'points' => $questionPoints[$id]]])->all());

        return back()->with('success', 'Kuis CBT berhasil dibuat.');
    }

    /**
     * Guru mapel membuka kuis saat jam pelajarannya, lalu menutup kembali
     * setelah selesai. Siswa hanya bisa masuk selagi kuis terbuka.
     */
    public function toggleOpen(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);

        abort_unless($quiz->is_published, 422, 'Publikasikan kuis terlebih dahulu sebelum membukanya.');

        $opening = ! $quiz->is_open;

        $quiz->update([
            'is_open' => $opening,
            'opened_at' => $opening ? now() : $quiz->opened_at,
        ]);

        return back()->with('success', $opening
            ? "Kuis \"{$quiz->title}\" dibuka. Siswa yang sudah absen pagi ini bisa mulai mengerjakan."
            : "Kuis \"{$quiz->title}\" ditutup. Siswa tidak bisa memulai kuis lagi.");
    }

    public function liveHost(Request $request, Quiz $quiz): View
    {
        $this->authorizeQuiz($request, $quiz);
        abort_unless($quiz->isLive(), 422, 'Kuis ini bukan mode Kahoot.');

        $quiz->load(['subject', 'classroom', 'questions']);

        return view('guru.kuis-live-host', compact('quiz'));
    }

    public function startLive(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);
        abort_unless($quiz->isLive() && $quiz->is_published, 422, 'Publikasikan kuis live terlebih dahulu.');
        abort_if($quiz->questions()->count() === 0, 422, 'Kuis belum memiliki soal.');

        $quiz->update([
            'is_open' => true,
            'opened_at' => now(),
            'live_phase' => 'question',
            'live_question_index' => 0,
            'live_question_started_at' => now(),
        ]);

        return back()->with('success', 'Sesi Kahoot dimulai. Siswa dapat masuk sekarang.');
    }

    public function advanceLive(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);
        abort_unless($quiz->isLive() && $quiz->is_open, 422, 'Sesi live belum dibuka.');

        $questionCount = $quiz->questions()->count();
        if ($quiz->live_phase === 'question') {
            $quiz->update(['live_phase' => 'reveal']);
        } elseif ($quiz->live_phase === 'reveal' && $quiz->live_question_index < $questionCount - 1) {
            $quiz->update([
                'live_phase' => 'question',
                'live_question_index' => $quiz->live_question_index + 1,
                'live_question_started_at' => now(),
            ]);
        } else {
            $this->finishLiveQuiz($quiz);
        }

        return back();
    }

    public function finishLive(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);
        $this->finishLiveQuiz($quiz);

        return back()->with('success', 'Sesi Kahoot selesai dan seluruh nilai dihitung.');
    }

    public function liveStart(Request $request, Quiz $quiz, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }
        abort_unless($quiz->isLive() && $quiz->classroom_id === $student->classroom_id && $quiz->is_published, 403);
        abort_unless(Attendance::hasCheckedInToday($student->id), 422, 'Absen QR terlebih dahulu.');

        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => $student->id],
            ['started_at' => now(), 'total_questions' => $quiz->questions()->count()]
        );
        $quiz->load(['subject', 'questions']);

        return view('siswa.kuis-live', compact('quiz', 'attempt', 'student'));
    }

    public function liveState(Request $request, Quiz $quiz, CurrentStudentResolver $resolver)
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return response()->json(['message' => 'Pilih siswa terlebih dahulu.'], 422);
        }
        abort_unless($quiz->classroom_id === $student->classroom_id && $quiz->isLive(), 403);

        $questions = $quiz->questions()->get();
        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => $student->id],
            ['started_at' => now(), 'total_questions' => $questions->count()]
        );
        $question = $quiz->live_question_index === null ? null : $questions->get($quiz->live_question_index);
        $answer = $question ? $attempt->answers()->where('quiz_question_id', $question->id)->first() : null;

        return response()->json([
            'phase' => $quiz->live_phase,
            'is_open' => $quiz->is_open,
            'index' => $quiz->live_question_index,
            'total' => $questions->count(),
            'question' => $question ? [
                'id' => $question->id,
                'text' => $question->question,
                'options' => $question->options,
                'media_url' => $question->media_path ? Storage::disk('public')->url($question->media_path) : null,
                'media_type' => $question->media_type,
                'correct' => $quiz->live_phase === 'reveal' ? $question->correct_answer : null,
            ] : null,
            'answer' => $answer?->answer,
            'score' => $this->liveScore($attempt, $questions),
            'show_score' => $quiz->show_score_per_question,
            'correct_count' => $attempt->answers()->where('is_correct', true)->count(),
        ]);
    }

    public function liveAnswer(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->assertAttemptOwner($request, $attempt);
        $quiz = $attempt->quiz;
        abort_unless($quiz->isLive() && $quiz->live_phase === 'question', 422);
        $question = $quiz->questions()->get()->get($quiz->live_question_index);
        abort_unless($question, 422);
        $data = $request->validate(['answer' => ['nullable', 'in:A,B,C,D']]);
        $isCorrect = $data['answer'] === $question->correct_answer;
        QuizAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id],
            ['answer' => $data['answer'] ?? null, 'is_correct' => $isCorrect, 'awarded_points' => $isCorrect ? $question->pivot->points : 0]
        );

        return response()->json(['ok' => true]);
    }

    public function available(Request $request, CurrentStudentResolver $resolver, QuizAttemptFinalizer $finalizer): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        // Rapikan dulu kuis siswa ini yang waktunya sudah habis, supaya
        // daftar yang tampil menunjukkan nilai akhirnya, bukan "sedang
        // dikerjakan" selamanya.
        QuizAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->with('quiz')
            ->get()
            ->each(fn (QuizAttempt $attempt) => $finalizer->finalizeIfExpired($attempt));

        $quizzes = Quiz::with(['subject', 'creator', 'attempts' => fn ($query) => $query->where('student_id', $student->id)])
            ->where('classroom_id', $student->classroom_id)->where('is_published', true)->latest()->get();

        $hasCheckedIn = Attendance::hasCheckedInToday($student->id);
        $leaderboard = $this->classroomLeaderboard($student->classroom_id);

        return view('siswa.kuis-ranking', compact('student', 'quizzes', 'hasCheckedIn', 'leaderboard'));
    }

    public function start(Request $request, Quiz $quiz, CurrentStudentResolver $resolver, QuizAttemptFinalizer $finalizer): RedirectResponse|View
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }
        abort_unless($quiz->classroom_id === $student->classroom_id && $quiz->is_published, 403);

        // Syarat 1: guru mapel sedang membuka kuis di jam pelajarannya.
        if (! $quiz->isAvailable()) {
            return redirect()->route('siswa.kuis')
                ->with('error', 'Kuis belum dibuka oleh guru mata pelajaran. Tunggu sampai jam pelajarannya dimulai.');
        }

        // Syarat 2: siswa sudah scan presensi masuk pagi ini.
        if (! Attendance::hasCheckedInToday($student->id)) {
            return redirect()->route('siswa.kuis')
                ->with('error', 'Belum bisa mengerjakan kuis. Silakan scan QR presensi di sekolah terlebih dahulu.');
        }

        $attempt = QuizAttempt::firstOrCreate(['quiz_id' => $quiz->id, 'student_id' => $student->id], ['started_at' => now(), 'total_questions' => $quiz->questions()->count()]);

        if ($finalizer->finalizeIfExpired($attempt)) {
            return redirect()->route('siswa.kuis')
                ->with('error', "Waktu pengerjaan kuis ini sudah habis. Kuis otomatis dikumpulkan dengan nilai {$attempt->fresh()->score}.");
        }

        abort_if($attempt->status === 'submitted', 422, 'Kuis sudah dikumpulkan.');
        $attempt->load('answers');
        $quiz->load('questions');

        return view('siswa.kuis', compact('quiz', 'attempt'));
    }

    public function answer(Request $request, QuizAttempt $attempt, QuizAttemptFinalizer $finalizer): RedirectResponse
    {
        $data = $request->validate(['quiz_question_id' => ['required', 'integer', 'exists:quiz_questions,id'], 'answer' => ['nullable', 'in:A,B,C,D']]);
        $this->assertAttemptOwner($request, $attempt);

        // Waktu habis di tengah pengerjaan: kumpulkan otomatis, jangan
        // biarkan siswa tergantung tanpa nilai.
        if ($finalizer->finalizeIfExpired($attempt)) {
            return redirect()->route('siswa.kuis')
                ->with('error', "Waktu pengerjaan habis. Kuis otomatis dikumpulkan dengan nilai {$attempt->fresh()->score}.");
        }

        $question = $attempt->quiz->questions()->whereKey($data['quiz_question_id'])->firstOrFail();
        abort_if($attempt->status === 'submitted', 422, 'Kuis sudah dikumpulkan.');
        $answer = $data['answer'] ?? null;
        $isCorrect = $answer !== null && $answer === $question->correct_answer;
        QuizAnswer::updateOrCreate(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id], ['answer' => $answer, 'is_correct' => $isCorrect, 'awarded_points' => $isCorrect ? $question->pivot->points : 0]);

        return back()->with('success', 'Jawaban tersimpan.');
    }

    public function submit(Request $request, QuizAttempt $attempt, QuizAttemptFinalizer $finalizer): RedirectResponse
    {
        $this->assertAttemptOwner($request, $attempt);
        abort_if($attempt->status === 'submitted', 422, 'Kuis sudah dikumpulkan.');

        if ($finalizer->finalizeIfExpired($attempt)) {
            return redirect()->route('siswa.kuis')
                ->with('success', "Waktu habis. Kuis otomatis dikumpulkan dengan nilai {$attempt->fresh()->score}.");
        }

        $finalizer->finalize($attempt);

        return redirect()->route('siswa.kuis')->with('success', 'Kuis berhasil dikumpulkan.');
    }

    public function results(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }
        $attempts = QuizAttempt::with(['quiz.subject', 'quiz.classroom'])->where('student_id', $student->id)->where('status', 'submitted')->latest('submitted_at')->get();

        return view('siswa.hasil-kuis', compact('student', 'attempts'));
    }

    private function authorizeSubject(Request $request, int $subjectId): void
    {
        abort_unless(
            $request->user()->hasRole('admin')
            || Subject::whereKey($subjectId)->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id))->exists(),
            403
        );
    }

    /**
     * Kuis punya kelas, bukan cuma mapel: guru hanya boleh membuat kuis di
     * kelas yang benar-benar ia ajarkan untuk mapel tersebut. Guru tanpa
     * profil guru (belum ditugaskan mapel/kelas apa pun) tidak dibolehkan
     * sama sekali, bukan malah dibiarkan bebas.
     */
    private function authorizeClassroom(Request $request, int $subjectId, int $classroomId): void
    {
        $teacher = $this->teacherFor($request);

        abort_unless(
            $request->user()->hasRole('admin')
            || ($teacher !== null && $teacher->teaches($subjectId, $classroomId)),
            403,
            'Anda tidak mengajar mata pelajaran ini di kelas tersebut.'
        );
    }

    private function teacherFor(Request $request): ?Teacher
    {
        return Teacher::where('user_id', $request->user()->id)->first();
    }

    /**
     * Peringkat kelas berdasarkan rata-rata nilai kuis yang sudah
     * dikumpulkan. Siswa yang belum pernah mengerjakan tidak masuk daftar.
     *
     * @return Collection<int, QuizAttempt>
     */
    private function classroomLeaderboard(?int $classroomId): Collection
    {
        if (! $classroomId) {
            return collect();
        }

        $quizIds = Quiz::query()->where('classroom_id', $classroomId)->pluck('id');

        if ($quizIds->isEmpty()) {
            return collect();
        }

        return QuizAttempt::query()
            ->with('student')
            ->select('student_id')
            ->selectRaw('AVG(score) as average_score')
            ->selectRaw('COUNT(*) as quiz_count')
            ->whereIn('quiz_id', $quizIds)
            ->where('status', 'submitted')
            ->groupBy('student_id')
            ->orderByDesc('average_score')
            ->orderByDesc('quiz_count')
            ->get();
    }

    private function authorizeQuiz(Request $request, Quiz $quiz): void
    {
        abort_unless($request->user()->hasRole('admin') || $quiz->created_by === $request->user()->id, 403);
    }

    private function authorizeQuestion(Request $request, QuizQuestion $question): void
    {
        abort_unless($request->user()->hasRole('admin') || $question->created_by === $request->user()->id, 403);
    }

    private function assertAttemptOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($request->user()->hasRole('siswa') && $request->user()->student?->is($attempt->student), 403);
    }

    private function liveScore(QuizAttempt $attempt, Collection $questions): float
    {
        $total = $questions->sum(fn ($question) => $question->pivot->points);

        return $total > 0 ? round($attempt->answers()->sum('awarded_points') / $total * 100, 2) : 0;
    }

    private function finishLiveQuiz(Quiz $quiz): void
    {
        $quiz->attempts()->where('status', 'in_progress')->get()->each(
            fn (QuizAttempt $attempt) => app(QuizAttemptFinalizer::class)->finalize($attempt)
        );
        $quiz->update(['is_open' => false, 'live_phase' => 'finished']);
    }

    private function storeQuestionMedia(Request $request, array &$data): void
    {
        unset($data['media']);

        if (! $request->hasFile('media')) {
            return;
        }

        $file = $request->file('media');
        $data['media_path'] = $file->store('quiz-media', 'public');
        $data['media_type'] = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
    }

    private function deleteQuestionMedia(QuizQuestion $question): void
    {
        if ($question->media_path) {
            Storage::disk('public')->delete($question->media_path);
        }
    }
}
