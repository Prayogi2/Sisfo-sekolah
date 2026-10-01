<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Isi kolom per jenis soal:
 * - single/multiple: options = {A..D: teks}, correct_answer = [huruf kunci]
 * - essay: options = [], correct_answer = [kunci/pedoman jawaban] (boleh kosong)
 * - matching: options = [{left, right}, ...] (pasangan yang benar), correct_answer = null
 */
#[Fillable(['subject_id', 'created_by', 'type', 'question', 'media_path', 'media_type', 'options', 'correct_answer', 'explanation', 'points', 'is_active'])]
class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_SINGLE = 'single';

    public const TYPE_MULTIPLE = 'multiple';

    public const TYPE_ESSAY = 'essay';

    public const TYPE_MATCHING = 'matching';

    public const OPTION_KEYS = ['A', 'B', 'C', 'D'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_answer' => 'array', 'points' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_SINGLE => 'Pilihan Ganda (satu jawaban)',
            self::TYPE_MULTIPLE => 'Pilihan Ganda Kompleks (banyak jawaban)',
            self::TYPE_ESSAY => 'Essay / Uraian',
            self::TYPE_MATCHING => 'Menjodohkan',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function isChoice(): bool
    {
        return in_array($this->type, [self::TYPE_SINGLE, self::TYPE_MULTIPLE], true);
    }

    /**
     * Pasangan menjodohkan yang benar.
     *
     * @return list<array{left: string, right: string}>
     */
    public function matchingPairs(): array
    {
        return $this->type === self::TYPE_MATCHING ? array_values($this->options ?? []) : [];
    }

    /**
     * Pilihan sisi kanan yang diacak (tetap sama tiap dimuat untuk soal
     * yang sama), supaya urutannya tidak membocorkan jawaban.
     *
     * @return list<string>
     */
    public function shuffledMatchingChoices(): array
    {
        $choices = array_column($this->matchingPairs(), 'right');
        usort($choices, fn (string $a, string $b) => strcmp(md5($this->id.$a), md5($this->id.$b)));

        return $choices;
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_quiz_question')->withPivot(['sort_order', 'points']);
    }
}
