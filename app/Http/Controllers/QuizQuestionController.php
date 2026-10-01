<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesQuizContent;
use App\Http\Requests\Quiz\StoreQuizQuestionsRequest;
use App\Http\Requests\Quiz\UpdateQuizQuestionRequest;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Services\QuizQuestionForm;
use App\Services\QuizQuestionSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Bank soal guru: tambah banyak soal sekaligus (4 jenis), edit, dan
 * import dari Excel.
 */
class QuizQuestionController extends Controller
{
    use AuthorizesQuizContent;

    public function create(Request $request): View
    {
        return view('guru.bank-soal-form', [
            'question' => null,
            'subjects' => $this->subjectsFor($request),
            'types' => QuizQuestion::typeLabels(),
        ]);
    }

    public function store(StoreQuizQuestionsRequest $request): RedirectResponse
    {
        $subjectId = $request->integer('subject_id');
        $this->authorizeSubject($request, $subjectId);

        $items = $request->validated('questions');

        DB::transaction(function () use ($request, $items, $subjectId) {
            foreach ($items as $index => $item) {
                QuizQuestion::create([
                    ...QuizQuestionForm::attributes($item),
                    ...$this->mediaAttributes($request->file("questions.{$index}.media")),
                    'subject_id' => $subjectId,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('guru.bank-soal', ['mapel' => $subjectId])->with('success', count($items).' soal berhasil ditambahkan ke bank soal.');
    }

    public function edit(Request $request, QuizQuestion $question): View
    {
        $this->authorizeQuestion($request, $question);

        return view('guru.bank-soal-form', [
            'question' => $question,
            'subjects' => $this->subjectsFor($request),
            'types' => QuizQuestion::typeLabels(),
        ]);
    }

    public function update(UpdateQuizQuestionRequest $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        $subjectId = $request->integer('subject_id');
        $this->authorizeSubject($request, $subjectId);

        $attributes = QuizQuestionForm::attributes($request->validated('questions.0')) + ['subject_id' => $subjectId];
        $newMedia = $request->file('questions.0.media');

        if ($newMedia || $request->boolean('questions.0.remove_media')) {
            if ($question->media_path) {
                Storage::disk('public')->delete($question->media_path);
            }
            $attributes += $newMedia ? $this->mediaAttributes($newMedia) : ['media_path' => null, 'media_type' => null];
        }

        $question->update($attributes);

        return redirect()->route('guru.bank-soal', ['mapel' => $subjectId])->with('success', 'Soal berhasil diperbarui.');
    }

    public function importTemplate(QuizQuestionSpreadsheet $spreadsheet)
    {
        return $spreadsheet->template();
    }

    public function import(Request $request, QuizQuestionSpreadsheet $spreadsheet): RedirectResponse
    {
        $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'subject_id.required' => 'Pilih mata pelajaran untuk soal yang di-import.',
            'file.required' => 'Pilih file Excel (.xlsx) berisi soal.',
            'file.mimes' => 'File harus berformat .xlsx (gunakan template yang disediakan).',
        ]);
        $subjectId = $request->integer('subject_id');
        $this->authorizeSubject($request, $subjectId);

        $items = $spreadsheet->parse($request->file('file'));

        DB::transaction(function () use ($request, $items, $subjectId) {
            foreach ($items as $item) {
                QuizQuestion::create([
                    ...QuizQuestionForm::attributes($item),
                    'subject_id' => $subjectId,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('guru.bank-soal', ['mapel' => $subjectId])->with('success', count($items).' soal berhasil di-import dari Excel.');
    }

    /**
     * Guru hanya bisa memilih mapel yang ia ajarkan; admin semua mapel.
     *
     * @return Collection<int, Subject>
     */
    private function subjectsFor(Request $request): Collection
    {
        return Subject::query()
            ->when(
                $this->actingAsGuru($request),
                fn ($query) => $query->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id))
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{media_path?: string, media_type?: string}
     */
    private function mediaAttributes(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        return [
            'media_path' => $file->store('quiz-media', 'public'),
            'media_type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image',
        ];
    }
}
