<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class QuizQuestionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: Subject}
     */
    private function guruTeachingASubject(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $subject = Subject::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => Classroom::factory()->create()->id]);

        return [$user, $subject];
    }

    public function test_the_add_question_page_offers_all_four_question_types(): void
    {
        [$user] = $this->guruTeachingASubject();

        $this->actingAs($user)->get(route('guru.bank-soal.tambah'))
            ->assertOk()
            ->assertSeeInOrder(['Pilihan Ganda (satu jawaban)', 'Pilihan Ganda Kompleks (banyak jawaban)', 'Essay / Uraian', 'Menjodohkan']);
    }

    public function test_the_question_bank_is_split_into_a_tab_per_subject_the_guru_teaches(): void
    {
        [$user, $indonesian] = $this->guruTeachingASubject();
        $math = Subject::factory()->create(['name' => 'Matematika Dasar']);
        Teacher::where('user_id', $user->id)->sole()->teachingAssignments()->create(['subject_id' => $math->id, 'classroom_id' => Classroom::factory()->create()->id]);
        QuizQuestion::factory()->create(['subject_id' => $indonesian->id, 'created_by' => $user->id, 'question' => 'Soal bahasa']);
        QuizQuestion::factory()->count(2)->create(['subject_id' => $math->id, 'created_by' => $user->id]);

        $this->actingAs($user)->get(route('guru.bank-soal', ['mapel' => $math->id]))
            ->assertOk()
            ->assertSee('data-bs-target="#bank-mapel-'.$indonesian->id.'"', false)
            ->assertSee('data-bs-target="#bank-mapel-'.$math->id.'"', false)
            ->assertSee('Tambah Soal Matematika Dasar')
            ->assertSee('id="bank-mapel-'.$math->id.'" role="tabpanel"', false)
            ->assertSee('tab-pane fade show active" id="bank-mapel-'.$math->id.'"', false);
    }

    public function test_guru_can_save_several_questions_of_every_type_at_once(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();

        $this->actingAs($user)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $subject->id,
            'questions' => [
                ['type' => 'single', 'question' => 'Ibu kota Indonesia?', 'options' => ['A' => 'Bandung', 'B' => 'Jakarta', 'C' => 'Medan', 'D' => 'Solo'], 'correct_answer' => ['B'], 'points' => 1],
                ['type' => 'multiple', 'question' => 'Bilangan genap?', 'options' => ['A' => '2', 'B' => '3', 'C' => '4', 'D' => '5'], 'correct_answer' => ['C', 'A'], 'points' => 2],
                ['type' => 'essay', 'question' => 'Sebutkan 3 hewan herbivora!', 'answer_key' => 'Sapi, kambing, kelinci', 'points' => 3],
                ['type' => 'matching', 'question' => 'Jodohkan hewan dan suaranya', 'pairs' => [['left' => 'Kucing', 'right' => 'Mengeong'], ['left' => 'Anjing', 'right' => 'Menggonggong']], 'points' => 2],
            ],
        ])->assertRedirect(route('guru.bank-soal', ['mapel' => $subject->id]))->assertSessionHas('success', '4 soal berhasil ditambahkan ke bank soal.');

        $questions = QuizQuestion::orderBy('id')->get();
        $this->assertSame(['single', 'multiple', 'essay', 'matching'], $questions->pluck('type')->all());
        $this->assertSame(['A', 'C'], $questions[1]->correct_answer);
        $this->assertSame(['Sapi, kambing, kelinci'], $questions[2]->correct_answer);
        $this->assertSame([['left' => 'Kucing', 'right' => 'Mengeong'], ['left' => 'Anjing', 'right' => 'Menggonggong']], $questions[3]->options);
        $this->assertTrue($questions->every(fn (QuizQuestion $question) => $question->subject_id === $subject->id && $question->created_by === $user->id));
    }

    public function test_an_invalid_question_rejects_the_whole_batch_with_a_numbered_message(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();

        $this->actingAs($user)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $subject->id,
            'questions' => [
                ['type' => 'essay', 'question' => 'Soal benar', 'points' => 1],
                ['type' => 'matching', 'question' => 'Jodohkan', 'pairs' => [['left' => 'Kucing', 'right' => 'Mengeong']]],
            ],
        ])->assertSessionHasErrors(['questions.1.pairs' => 'Soal #2: isi minimal 2 pasangan untuk soal menjodohkan.']);

        $this->assertDatabaseCount('quiz_questions', 0);
    }

    public function test_matching_answers_on_the_right_side_must_be_unique(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();

        $this->actingAs($user)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $subject->id,
            'questions' => [
                ['type' => 'matching', 'question' => 'Jodohkan', 'pairs' => [['left' => 'Kucing', 'right' => 'Hewan'], ['left' => 'Anjing', 'right' => 'hewan']]],
            ],
        ])->assertSessionHasErrors(['questions.0.pairs.1.right' => 'Soal #1: jawaban di sisi kanan tidak boleh ada yang sama.']);
    }

    public function test_guru_can_edit_a_question_and_change_its_type(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => $user->id]);

        $this->actingAs($user)->get(route('guru.bank-soal.edit', $question))->assertOk()->assertSee($question->question);

        $this->actingAs($user)->put(route('guru.bank-soal.update', $question), [
            'subject_id' => $subject->id,
            'questions' => [['type' => 'essay', 'question' => 'Jelaskan daur air!', 'answer_key' => 'Penguapan, pengembunan, hujan', 'points' => 5]],
        ])->assertRedirect(route('guru.bank-soal', ['mapel' => $subject->id]))->assertSessionHas('success', 'Soal berhasil diperbarui.');

        $question->refresh();
        $this->assertSame(['essay', 'Jelaskan daur air!', 5, ['Penguapan, pengembunan, hujan']], [$question->type, $question->question, $question->points, $question->correct_answer]);
    }

    public function test_guru_cannot_edit_a_question_made_by_another_teacher(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => User::factory()->create(['role' => 'guru'])->id]);

        $this->actingAs($user)->get(route('guru.bank-soal.edit', $question))->assertForbidden();
        $this->actingAs($user)->put(route('guru.bank-soal.update', $question), [
            'subject_id' => $subject->id,
            'questions' => [['type' => 'essay', 'question' => 'Diubah orang lain']],
        ])->assertForbidden();
    }

    public function test_the_import_template_can_be_downloaded(): void
    {
        [$user] = $this->guruTeachingASubject();

        $this->actingAs($user)->get(route('guru.bank-soal.import.template'))
            ->assertOk()
            ->assertDownload('template-import-soal.xlsx');
    }

    public function test_questions_of_every_type_can_be_imported_from_excel(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();
        $file = $this->spreadsheet([
            ['PG', 'Ibu kota Indonesia?', 'Bandung', 'Jakarta', 'Medan', 'Solo', 'B', '', 1, ''],
            ['PG Kompleks', 'Bilangan genap?', '2', '3', '4', '5', 'a, c', '', 2, ''],
            ['Essay', 'Sebutkan 3 hewan herbivora!', '', '', '', '', 'Sapi, kambing', '', '', ''],
            ['Menjodohkan', 'Jodohkan hewan dan suaranya', '', '', '', '', '', "Kucing = Mengeong\nAnjing = Menggonggong", 3, ''],
        ]);

        $this->actingAs($user)->post(route('guru.bank-soal.import'), ['subject_id' => $subject->id, 'file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '4 soal berhasil di-import dari Excel.');

        $questions = QuizQuestion::orderBy('id')->get();
        $this->assertSame(['single', 'multiple', 'essay', 'matching'], $questions->pluck('type')->all());
        $this->assertSame(['A', 'C'], $questions[1]->correct_answer);
        $this->assertSame(1, $questions[2]->points);
        $this->assertSame('Menggonggong', $questions[3]->options[1]['right']);
    }

    public function test_the_official_kahoot_template_can_be_imported(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Quiz template'],
            ['Add questions, at least two answer alternatives, time limit and choose correct answers (at least one).'],
            [],
            ['', 'Question - max 120 characters', 'Answer 1 - max 75 characters', 'Answer 2 - max 75 characters', 'Answer 3 - max 75 characters', 'Answer 4 - max 75 characters', 'Time limit (sec) – 5, 10, 20, 30, 60, 90, 120, or 240 secs', 'Correct answer(s) - choose at least one'],
            [1, 'Ibu kota Indonesia?', 'Bandung', 'Jakarta', 'Medan', 'Solo', 20, 2],
            [2, 'Bilangan genap?', '2', '3', '4', '5', 30, '1,3'],
        ], null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'kahoot').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $file = new UploadedFile($path, 'kahoot.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($user)->post(route('guru.bank-soal.import'), ['subject_id' => $subject->id, 'file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 soal berhasil di-import dari Excel.');

        $questions = QuizQuestion::orderBy('id')->get();
        $this->assertSame(['single', ['B']], [$questions[0]->type, $questions[0]->correct_answer]);
        $this->assertSame(['multiple', ['A', 'C']], [$questions[1]->type, $questions[1]->correct_answer]);
        $this->assertSame('Jakarta', $questions[0]->options['B']);
    }

    public function test_an_excel_file_with_an_invalid_row_imports_nothing_and_names_the_row(): void
    {
        [$user, $subject] = $this->guruTeachingASubject();
        $file = $this->spreadsheet([
            ['PG', 'Soal benar', 'a', 'b', 'c', 'd', 'A', '', 1, ''],
            ['Tebak Gambar', 'Jenis tidak dikenal', '', '', '', '', '', '', 1, ''],
        ]);

        $this->actingAs($user)->post(route('guru.bank-soal.import'), ['subject_id' => $subject->id, 'file' => $file])
            ->assertSessionHasErrors(['file' => 'Baris 3: jenis soal "Tebak Gambar" tidak dikenal. Gunakan PG, PG Kompleks, Essay, atau Menjodohkan.']);

        $this->assertDatabaseCount('quiz_questions', 0);
    }

    /**
     * @param  list<list<string|int>>  $rows
     */
    private function spreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Jenis', 'Pertanyaan', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Kunci Jawaban', 'Pasangan Menjodohkan', 'Poin', 'Pembahasan'],
            ...$rows,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'soal').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile($path, 'soal.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
