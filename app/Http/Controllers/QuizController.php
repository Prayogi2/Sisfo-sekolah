<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesQuizContent;
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
use App\Services\LiveQuizSession;
use App\Services\QuizAnswerGrader;
use App\Services\QuizAttemptFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    use AuthorizesQuizContent, ResolvesCurrentStudent;

    public function questionBank(Request $request): View
    {
        abort_unless($request->user()->hasRole(['admin', 'guru']), 403);

        $isGuru = $this->actingAsGuru($request);
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

        // Jumlah jawaban essay yang sudah dikirim siswa tapi belum dikoreksi, per kuis.
        $pendingEssays = QuizAnswer::query()
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_answers.quiz_attempt_id')
            ->join('quiz_questions', 'quiz_questions.id', '=', 'quiz_answers.quiz_question_id')
            ->where('quiz_questions.type', QuizQuestion::TYPE_ESSAY)
            ->whereNull('quiz_answers.graded_at')
            ->whereNotNull('quiz_answers.answer')
            ->whereIn('quiz_attempts.quiz_id', $quizzes->pluck('id'))
            ->groupBy('quiz_attempts.quiz_id')
            ->selectRaw('quiz_attempts.quiz_id, count(*) as total')
            ->pluck('total', 'quiz_id');

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

        return view($isGuru ? 'guru.bank-soal' : 'admin.bank-soal', [
            'subjects' => $subjects,
            'classrooms' => $classrooms,
            'subjectClassroomMap' => $subjectClassroomMap,
            'questions' => $questions,
            'quizzes' => $quizzes,
            'pendingEssays' => $pendingEssays,
        ]);
    }

    public function destroyQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        if ($question->quizzes()->whereHas('attempts')->exists()) {
            return back()->with('error', 'Soal yang sudah dipakai dalam kuis tidak dapat dihapus.');
        }
        $this->deleteQuestionMedia($question);
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function storeQuiz(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'], 'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'mode' => ['sometimes', 'in:live,async'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'question_minutes' => ['nullable', 'numeric', 'min:0.25', 'max:30'],
            'show_score_per_question' => ['sometimes', 'boolean'], 'starts_at' => ['nullable', 'date'],
            'question_ids' => ['required', 'array', 'min:1'], 'question_ids.*' => ['integer', 'exists:quiz_questions,id'],
        ], [
            'subject_id.required' => 'Pilih mata pelajaran untuk kuis ini.',
            'classroom_id.required' => 'Pilih kelas yang akan mengerjakan kuis ini.',
            'question_ids.required' => 'Pilih minimal satu soal dari bank soal untuk dimasukkan ke kuis ini.',
            'question_ids.min' => 'Pilih minimal satu soal dari bank soal untuk dimasukkan ke kuis ini.',
            'question_minutes.min' => 'Waktu per soal minimal 0,25 menit (15 detik).',
            'question_minutes.max' => 'Waktu per soal maksimal 30 menit.',
        ]);
        $this->authorizeSubject($request, (int) $data['subject_id']);
        $this->authorizeClassroom($request, (int) $data['subject_id'], (int) $data['classroom_id']);
        $questionIds = $data['question_ids'];
        $validQuestionIds = QuizQuestion::where('subject_id', $data['subject_id'])->whereIn('id', $questionIds)->pluck('id')->all();
        if (count($validQuestionIds) !== count($questionIds)) {
            return back()->withInput()->with('error', 'Semua soal harus berasal dari mata pelajaran kuis.');
        }

        $startsAt = filled($data['starts_at'] ?? null) ? Carbon::parse($data['starts_at']) : null;

        $quiz = Quiz::create([
            ...collect($data)->except(['question_ids', 'question_minutes', 'starts_at'])->all(),
            'created_by' => $request->user()->id,
            // Kahoot: guru memindahkan soal & tiap soal ada hitung mundur.
            // Mandiri: siswa bebas pindah soal dalam durasi total.
            'mode' => $data['mode'] ?? 'live',
            'question_seconds' => (int) round(($data['question_minutes'] ?? 0.5) * 60),
            'show_score_per_question' => (bool) ($data['show_score_per_question'] ?? false),
            'live_phase' => 'lobby',
            // Waktu selesai selalu mengikuti waktu mulai + durasi, tidak diisi manual.
            'starts_at' => $startsAt,
            'ends_at' => $startsAt?->copy()->addMinutes((int) $data['duration_minutes']),
            // Kuis baru selalu Draft; guru mempublikasikannya saat siap dipakai.
            'is_published' => false,
        ]);
        $questionPoints = QuizQuestion::whereIn('id', $questionIds)->pluck('points', 'id');
        $quiz->questions()->attach(collect($questionIds)->values()->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index + 1, 'points' => $questionPoints[$id]]])->all());

        return back()->with('success', "Kuis \"{$quiz->title}\" disimpan sebagai Draft. Klik Publikasikan bila sudah siap dipakai siswa.");
    }

    /**
     * Draft → Dipublikasikan (siswa bisa melihat kuis), atau kembalikan ke
     * Draft bila belum mau dipakai. Kuis yang sedang dibuka harus ditutup
     * dulu supaya siswa yang sedang mengerjakan tidak terputus.
     */
    public function togglePublish(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);

        if ($quiz->is_published && $quiz->is_open) {
            return back()->with('error', "Tutup kuis \"{$quiz->title}\" dulu sebelum mengembalikannya ke Draft.");
        }

        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('success', $quiz->is_published
            ? "Kuis \"{$quiz->title}\" dipublikasikan. Klik Buka Kuis saat jam pelajaran untuk mulai."
            : "Kuis \"{$quiz->title}\" dikembalikan ke Draft dan tidak terlihat oleh siswa.");
    }

    /**
     * Guru mapel membuka kuis saat jam pelajarannya, lalu menutup kembali
     * setelah selesai. Siswa hanya bisa masuk selagi kuis terbuka.
     */
    public function toggleOpen(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);

        if (! $quiz->is_published) {
            return back()->with('error', 'Publikasikan kuis terlebih dahulu sebelum membukanya.');
        }

        $opening = ! $quiz->is_open;

        $quiz->update([
            'is_open' => $opening,
            'opened_at' => $opening ? now() : $quiz->opened_at,
        ]);

        return back()->with('success', $opening
            ? "Kuis \"{$quiz->title}\" dibuka. Siswa yang sudah absen pagi ini bisa mulai mengerjakan."
            : "Kuis \"{$quiz->title}\" ditutup. Siswa tidak bisa memulai kuis lagi.");
    }

    public function liveHost(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);
        if (! $quiz->isLive()) {
            return redirect()->route('guru.bank-soal')->with('error', 'Kuis ini bukan mode Kahoot.');
        }

        $quiz->load(['subject', 'classroom', 'questions']);

        return view('guru.kuis-live-host', compact('quiz'));
    }

    public function startLive(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);
        if (! ($quiz->isLive() && $quiz->is_published)) {
            return back()->with('error', 'Publikasikan kuis live terlebih dahulu.');
        }
        if ($quiz->questions()->count() === 0) {
            return back()->with('error', 'Kuis belum memiliki soal.');
        }

        // Mengulang sesi = mulai dari nol: jawaban sesi sebelumnya dihapus
        // supaya siswa bisa menjawab lagi (satu soal hanya boleh dijawab sekali).
        DB::transaction(function () use ($quiz) {
            QuizAnswer::query()->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->delete();
            $quiz->attempts()->update(['status' => 'in_progress', 'submitted_at' => null, 'score' => null, 'correct_answers' => 0]);
        });

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
        if (! ($quiz->isLive() && $quiz->is_open)) {
            return back()->with('error', 'Sesi live belum dibuka.');
        }

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
        if (! Attendance::hasCheckedInToday($student->id)) {
            return redirect()->route('siswa.kuis')->with('error', 'Belum bisa mengerjakan kuis. Silakan scan QR presensi di sekolah terlebih dahulu.');
        }

        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => $student->id],
            ['started_at' => now(), 'total_questions' => $quiz->questions()->count()]
        );
        $quiz->load(['subject', 'questions']);

        return view('siswa.kuis-live', compact('quiz', 'attempt', 'student'));
    }

    public function liveState(Request $request, Quiz $quiz, CurrentStudentResolver $resolver, LiveQuizSession $session)
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

        $showRanking = in_array($quiz->live_phase, ['reveal', 'finished'], true);
        $ranking = $showRanking ? $session->leaderboard($quiz, null) : collect();

        return response()->json([
            'phase' => $quiz->live_phase,
            'is_open' => $quiz->is_open,
            'index' => $quiz->live_question_index,
            'total' => $questions->count(),
            'question' => $question ? $this->liveQuestionPayload($question, $quiz->live_phase === 'reveal') : null,
            'answer' => $answer?->answer,
            'time_limit' => $question ? $session->timeLimit($quiz, $question) : null,
            'seconds_left' => $question ? $session->secondsLeft($quiz, $question) : null,
            'gained_points' => $answer?->game_points,
            'game_points' => (int) $attempt->answers()->sum('game_points'),
            'rank' => $ranking->firstWhere('student_id', $student->id)['rank'] ?? null,
            'leaderboard' => $ranking->take(5)->values(),
            'score' => $this->liveScore($attempt, $questions),
            'show_score' => $quiz->show_score_per_question,
            'correct_count' => $attempt->answers()->where('is_correct', true)->count(),
        ]);
    }

    public function liveAnswer(Request $request, QuizAttempt $attempt, QuizAnswerGrader $grader, LiveQuizSession $session): JsonResponse
    {
        $this->assertAttemptOwner($request, $attempt);
        $quiz = $attempt->quiz;
        abort_unless($quiz->isLive() && $quiz->live_phase === 'question', 422);
        $question = $quiz->questions()->get()->get($quiz->live_question_index);
        abort_unless($question, 422);

        // Seperti Kahoot: satu kali jawab per soal, dan hanya selama waktunya berjalan.
        if ($attempt->answers()->where('quiz_question_id', $question->id)->whereNotNull('answer')->exists()) {
            return response()->json(['message' => 'Kamu sudah menjawab soal ini.'], 422);
        }
        if (! $session->acceptsAnswers($quiz, $question)) {
            return response()->json(['message' => 'Waktu menjawab sudah habis.'], 422);
        }

        $points = (int) $question->pivot->points;
        $graded = $grader->grade($question, $points, $request->input('answer'));
        $responseMs = $session->responseMs($quiz);

        QuizAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id],
            $graded + [
                'response_ms' => $responseMs,
                'game_points' => $session->gamePoints($quiz, $question, $graded['awarded_points'], $points, $responseMs),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Lembar soal siap cetak/simpan PDF untuk siswa yang mengerjakan di
     * kertas; ?kunci=1 menampilkan versi kunci jawaban untuk guru.
     */
    public function printSheet(Request $request, Quiz $quiz): View
    {
        $this->authorizeQuiz($request, $quiz);

        return view('guru.kuis-cetak', [
            'quiz' => $quiz->load(['subject', 'classroom', 'questions']),
            'withKey' => $request->boolean('kunci'),
        ]);
    }

    /**
     * Data layar proyektor guru (soal, hitung mundur, jumlah yang sudah
     * menjawab, sebaran jawaban, dan papan peringkat).
     */
    public function liveHostState(Request $request, Quiz $quiz, LiveQuizSession $session): JsonResponse
    {
        $this->authorizeQuiz($request, $quiz);
        abort_unless($quiz->isLive(), 404);

        return response()->json($session->hostState($quiz));
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

        if ($attempt->status === 'submitted') {
            return redirect()->route('siswa.kuis')->with('error', 'Kuis sudah dikumpulkan.');
        }
        $attempt->load('answers');
        $quiz->load('questions');

        return view('siswa.kuis', compact('quiz', 'attempt'));
    }

    public function answer(Request $request, QuizAttempt $attempt, QuizAttemptFinalizer $finalizer, QuizAnswerGrader $grader): RedirectResponse
    {
        $data = $request->validate([
            'quiz_question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
        ]);
        $this->assertAttemptOwner($request, $attempt);

        // Waktu habis di tengah pengerjaan: kumpulkan otomatis, jangan
        // biarkan siswa tergantung tanpa nilai.
        if ($finalizer->finalizeIfExpired($attempt)) {
            return redirect()->route('siswa.kuis')
                ->with('error', "Waktu pengerjaan habis. Kuis otomatis dikumpulkan dengan nilai {$attempt->fresh()->score}.");
        }

        $question = $attempt->quiz->questions()->whereKey($data['quiz_question_id'])->firstOrFail();
        if ($attempt->status === 'submitted') {
            return back()->with('error', 'Kuis sudah dikumpulkan.');
        }
        try {
            $graded = $grader->grade($question, (int) $question->pivot->points, $request->input('answer'));
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }
        QuizAnswer::updateOrCreate(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id], $graded);

        return back()->with('success', 'Jawaban tersimpan.');
    }

    public function submit(Request $request, QuizAttempt $attempt, QuizAttemptFinalizer $finalizer): RedirectResponse
    {
        $this->assertAttemptOwner($request, $attempt);
        if ($attempt->status === 'submitted') {
            return redirect()->route('siswa.kuis')->with('error', 'Kuis sudah dikumpulkan.');
        }

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

    private function assertAttemptOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($request->user()->hasRole('siswa') && $request->user()->student?->is($attempt->student), 403);
    }

    /**
     * Data soal untuk layar siswa. Kunci jawaban baru dikirim saat fase
     * "reveal" supaya tidak bisa diintip lewat jaringan.
     *
     * @return array<string, mixed>
     */
    private function liveQuestionPayload(QuizQuestion $question, bool $reveal): array
    {
        return [
            'id' => $question->id,
            'text' => $question->question,
            'type' => $question->type,
            'options' => $question->isChoice() ? $question->options : null,
            'matching_left' => $question->type === QuizQuestion::TYPE_MATCHING ? array_column($question->matchingPairs(), 'left') : null,
            'matching_choices' => $question->type === QuizQuestion::TYPE_MATCHING ? $question->shuffledMatchingChoices() : null,
            'media_url' => $question->media_path ? Storage::disk('public')->url($question->media_path) : null,
            'media_type' => $question->media_type,
            'correct' => $reveal ? match ($question->type) {
                QuizQuestion::TYPE_MATCHING => array_column($question->matchingPairs(), 'right'),
                default => $question->correct_answer,
            } : null,
        ];
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

    private function deleteQuestionMedia(QuizQuestion $question): void
    {
        if ($question->media_path) {
            Storage::disk('public')->delete($question->media_path);
        }
    }
}
