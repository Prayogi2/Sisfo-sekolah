<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Bank soal Matematika setara kelas 5-6 MI, plus satu kuis CBT siap pakai.
 * Kunci jawaban sengaja tersebar (tidak selalu A) supaya kuis tidak bisa
 * ditebak asal pilih.
 */
class MathQuestionBankSeeder extends Seeder
{
    /**
     * @var list<array{question: string, options: array<string, string>, correct: string, explanation: string}>
     */
    public const QUESTIONS = [
        [
            'question' => 'Hasil dari 24 × 15 adalah ...',
            'options' => ['A' => '360', 'B' => '340', 'C' => '350', 'D' => '380'],
            'correct' => 'A',
            'explanation' => '24 × 15 = 24 × 10 + 24 × 5 = 240 + 120 = 360.',
        ],
        [
            'question' => 'Hasil dari 1.250 + 3.475 adalah ...',
            'options' => ['A' => '4.625', 'B' => '4.725', 'C' => '4.825', 'D' => '5.725'],
            'correct' => 'B',
            'explanation' => '1.250 + 3.475 = 4.725.',
        ],
        [
            'question' => 'Hasil dari 5.000 − 2.375 adalah ...',
            'options' => ['A' => '2.625', 'B' => '2.525', 'C' => '2.575', 'D' => '2.725'],
            'correct' => 'A',
            'explanation' => '5.000 − 2.375 = 2.625.',
        ],
        [
            'question' => 'Hasil dari 144 : 12 adalah ...',
            'options' => ['A' => '11', 'B' => '12', 'C' => '13', 'D' => '14'],
            'correct' => 'B',
            'explanation' => '12 × 12 = 144, jadi 144 : 12 = 12.',
        ],
        [
            'question' => 'FPB (Faktor Persekutuan Terbesar) dari 24 dan 36 adalah ...',
            'options' => ['A' => '12', 'B' => '6', 'C' => '8', 'D' => '18'],
            'correct' => 'A',
            'explanation' => '24 = 2³ × 3 dan 36 = 2² × 3², faktor persekutuan terbesarnya 2² × 3 = 12.',
        ],
        [
            'question' => 'KPK (Kelipatan Persekutuan Terkecil) dari 6 dan 8 adalah ...',
            'options' => ['A' => '12', 'B' => '18', 'C' => '48', 'D' => '24'],
            'correct' => 'D',
            'explanation' => 'Kelipatan 6: 6, 12, 18, 24. Kelipatan 8: 8, 16, 24. KPK-nya 24.',
        ],
        [
            'question' => 'Hasil dari 3/4 + 1/4 adalah ...',
            'options' => ['A' => '1', 'B' => '1/2', 'C' => '3/4', 'D' => '4/8'],
            'correct' => 'A',
            'explanation' => 'Penyebutnya sama, jadi 3/4 + 1/4 = 4/4 = 1.',
        ],
        [
            'question' => 'Nilai dari 2/5 bagian dari 100 adalah ...',
            'options' => ['A' => '20', 'B' => '40', 'C' => '50', 'D' => '60'],
            'correct' => 'B',
            'explanation' => '2/5 × 100 = 200 : 5 = 40.',
        ],
        [
            'question' => 'Luas persegi yang panjang sisinya 9 cm adalah ...',
            'options' => ['A' => '18 cm²', 'B' => '36 cm²', 'C' => '72 cm²', 'D' => '81 cm²'],
            'correct' => 'D',
            'explanation' => 'Luas persegi = sisi × sisi = 9 × 9 = 81 cm².',
        ],
        [
            'question' => 'Keliling persegi panjang dengan panjang 12 cm dan lebar 8 cm adalah ...',
            'options' => ['A' => '20 cm', 'B' => '48 cm', 'C' => '40 cm', 'D' => '96 cm'],
            'correct' => 'C',
            'explanation' => 'Keliling = 2 × (panjang + lebar) = 2 × (12 + 8) = 40 cm.',
        ],
        [
            'question' => 'Luas segitiga dengan alas 10 cm dan tinggi 6 cm adalah ...',
            'options' => ['A' => '16 cm²', 'B' => '30 cm²', 'C' => '60 cm²', 'D' => '80 cm²'],
            'correct' => 'B',
            'explanation' => 'Luas segitiga = ½ × alas × tinggi = ½ × 10 × 6 = 30 cm².',
        ],
        [
            'question' => 'Panjang 2 km sama dengan ... meter.',
            'options' => ['A' => '20', 'B' => '200', 'C' => '20.000', 'D' => '2.000'],
            'correct' => 'D',
            'explanation' => '1 km = 1.000 m, jadi 2 km = 2.000 m.',
        ],
        [
            'question' => 'Waktu 3 jam sama dengan ... menit.',
            'options' => ['A' => '180', 'B' => '30', 'C' => '90', 'D' => '300'],
            'correct' => 'A',
            'explanation' => '1 jam = 60 menit, jadi 3 jam = 3 × 60 = 180 menit.',
        ],
        [
            'question' => 'Berat 1,5 kg sama dengan ... gram.',
            'options' => ['A' => '15', 'B' => '150', 'C' => '1.500', 'D' => '15.000'],
            'correct' => 'C',
            'explanation' => '1 kg = 1.000 gram, jadi 1,5 kg = 1.500 gram.',
        ],
        [
            'question' => 'Rata-rata dari bilangan 7, 8, dan 9 adalah ...',
            'options' => ['A' => '7', 'B' => '8', 'C' => '9', 'D' => '24'],
            'correct' => 'B',
            'explanation' => '(7 + 8 + 9) : 3 = 24 : 3 = 8.',
        ],
        [
            'question' => 'Di antara bilangan berikut, yang merupakan bilangan prima adalah ...',
            'options' => ['A' => '9', 'B' => '12', 'C' => '15', 'D' => '13'],
            'correct' => 'D',
            'explanation' => '13 hanya habis dibagi 1 dan 13, sedangkan 9, 12, dan 15 punya pembagi lain.',
        ],
        [
            'question' => 'Nilai dari 45% dari 200 adalah ...',
            'options' => ['A' => '45', 'B' => '80', 'C' => '90', 'D' => '110'],
            'correct' => 'C',
            'explanation' => '45% × 200 = 45/100 × 200 = 90.',
        ],
        [
            'question' => 'Volume kubus dengan panjang rusuk 4 cm adalah ...',
            'options' => ['A' => '12 cm³', 'B' => '16 cm³', 'C' => '48 cm³', 'D' => '64 cm³'],
            'correct' => 'D',
            'explanation' => 'Volume kubus = rusuk × rusuk × rusuk = 4 × 4 × 4 = 64 cm³.',
        ],
        [
            'question' => 'Bentuk pecahan biasa dari bilangan desimal 0,25 adalah ...',
            'options' => ['A' => '1/2', 'B' => '2/5', 'C' => '1/4', 'D' => '1/5'],
            'correct' => 'C',
            'explanation' => '0,25 = 25/100, disederhanakan menjadi 1/4.',
        ],
        [
            'question' => 'Luas lingkaran dengan jari-jari 7 cm (π = 22/7) adalah ...',
            'options' => ['A' => '44 cm²', 'B' => '77 cm²', 'C' => '154 cm²', 'D' => '308 cm²'],
            'correct' => 'C',
            'explanation' => 'Luas = π × r × r = 22/7 × 7 × 7 = 154 cm².',
        ],
    ];

    public function run(): void
    {
        $subject = Subject::where('code', 'MTK')->first();

        if (! $subject) {
            $this->command?->warn('Mapel Matematika (MTK) belum ada. Jalankan SubjectSeeder dulu.');

            return;
        }

        $classroom = Classroom::query()->orderByDesc('grade_level')->first();

        if (! $classroom) {
            return;
        }

        // Pastikan ada guru pengampu di kelas ini, supaya bank soal ini
        // muncul juga di halaman guru (halaman itu menyaring berdasarkan
        // mapel & kelas yang diampu).
        $teacher = $subject->teachers()->first() ?? Teacher::query()->whereNotNull('user_id')->first();

        if ($teacher && ! $teacher->teaches($subject->id, $classroom->id)) {
            $teacher->teachingAssignments()->firstOrCreate([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
            ]);
        }

        $questions = collect(self::QUESTIONS)->map(fn (array $item) => QuizQuestion::firstOrCreate(
            ['subject_id' => $subject->id, 'question' => $item['question']],
            [
                'created_by' => $teacher?->user_id,
                'type' => 'multiple_choice',
                'options' => $item['options'],
                'correct_answer' => $item['correct'],
                'explanation' => $item['explanation'],
                'points' => 1,
                'is_active' => true,
            ],
        ));

        $quiz = Quiz::firstOrCreate(
            ['title' => 'Kuis Matematika - Operasi Hitung & Bangun Datar', 'classroom_id' => $classroom->id],
            [
                'subject_id' => $subject->id,
                'created_by' => $teacher?->user_id,
                'description' => 'Kuis latihan 10 soal pilihan ganda untuk mengukur pemahaman operasi hitung, pecahan, satuan, dan bangun datar.',
                'duration_minutes' => 30,
                // Semua kuis dikerjakan serentak (model Kahoot); guru yang
                // membuka sesinya lewat Panel Kahoot, jadi defaultnya tertutup.
                'mode' => 'live',
                'is_published' => true,
                'is_open' => false,
                'live_phase' => 'lobby',
            ],
        );

        if ($quiz->questions()->doesntExist()) {
            $quiz->questions()->attach(
                $questions->take(10)->values()
                    ->mapWithKeys(fn (QuizQuestion $question, int $index) => [
                        $question->id => ['sort_order' => $index + 1, 'points' => 1],
                    ])->all()
            );
        }

        $liveQuiz = Quiz::firstOrCreate(
            ['title' => 'Demo Kahoot Matematika - Serentak', 'classroom_id' => $classroom->id],
            [
                'subject_id' => $subject->id,
                'created_by' => $teacher?->user_id,
                'description' => 'Demo kuis serentak. Guru mengatur perpindahan soal dari Panel Kahoot.',
                'duration_minutes' => 30,
                'mode' => 'live',
                'show_score_per_question' => true,
                'is_published' => true,
                'is_open' => false,
                'live_phase' => 'lobby',
            ],
        );

        if ($liveQuiz->questions()->doesntExist()) {
            $liveQuiz->questions()->attach(
                $questions->take(10)->values()
                    ->mapWithKeys(fn (QuizQuestion $question, int $index) => [
                        $question->id => ['sort_order' => $index + 1, 'points' => 1],
                    ])->all()
            );
        }

        $this->command?->info('Bank soal demo siap: '.count(self::QUESTIONS).' soal Matematika.');
        $this->command?->info('Kuis mandiri: '.$quiz->title);
        $this->command?->info('Kuis live: '.$liveQuiz->title);
    }
}
