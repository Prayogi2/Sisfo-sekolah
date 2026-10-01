<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template & import soal dari Excel. Satu baris = satu soal, semua jenis
 * soal dalam satu sheet. Kolom:
 *
 * A Jenis | B Pertanyaan | C–F Opsi A–D | G Kunci Jawaban | H Pasangan
 * Menjodohkan | I Poin | J Pembahasan
 *
 * File template resmi Kahoot (Question, Answer 1–4, Time limit, Correct
 * answer(s) berupa nomor 1–4) juga diterima dan dibaca sebagai pilihan ganda.
 */
class QuizQuestionSpreadsheet
{
    private const HEADERS = ['Jenis', 'Pertanyaan', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Kunci Jawaban', 'Pasangan Menjodohkan', 'Poin', 'Pembahasan'];

    /**
     * Nama jenis di Excel (huruf kecil) → tipe soal.
     */
    private const TYPE_ALIASES = [
        'pg' => QuizQuestion::TYPE_SINGLE,
        'pilihan ganda' => QuizQuestion::TYPE_SINGLE,
        'pg kompleks' => QuizQuestion::TYPE_MULTIPLE,
        'pilihan ganda kompleks' => QuizQuestion::TYPE_MULTIPLE,
        'essay' => QuizQuestion::TYPE_ESSAY,
        'esai' => QuizQuestion::TYPE_ESSAY,
        'uraian' => QuizQuestion::TYPE_ESSAY,
        'menjodohkan' => QuizQuestion::TYPE_MATCHING,
        'pencocokan' => QuizQuestion::TYPE_MATCHING,
    ];

    private const FIRST_DATA_ROW = 2;

    public function __construct(private ReportExportService $exporter) {}

    public function template()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Soal');
        $sheet->fromArray([
            self::HEADERS,
            ['PG', 'Ibu kota Indonesia adalah ...', 'Bandung', 'Jakarta', 'Surabaya', 'Medan', 'B', '', 1, 'Jakarta adalah ibu kota Indonesia.'],
            ['PG Kompleks', 'Manakah yang termasuk bilangan genap?', '2', '3', '4', '5', 'A,C', '', 2, ''],
            ['Essay', 'Sebutkan 3 contoh hewan pemakan tumbuhan!', '', '', '', '', 'Sapi, kambing, kelinci (atau contoh lain yang benar)', '', 3, ''],
            ['Menjodohkan', 'Jodohkan hewan dengan suaranya!', '', '', '', '', '', "Kucing = Mengeong\nAnjing = Menggonggong\nAyam = Berkokok", 3, ''],
        ]);
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J6')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        foreach (['A' => 14, 'B' => 45, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 22, 'H' => 32, 'I' => 7, 'J' => 30] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $guide = $spreadsheet->createSheet()->setTitle('Petunjuk');
        $guide->fromArray(array_map(fn (string $line) => [$line], [
            'PETUNJUK PENGISIAN SOAL',
            '1. Isi soal di sheet "Soal", satu baris satu soal. Baris contoh boleh dihapus/diganti.',
            '2. Kolom Jenis diisi salah satu: PG, PG Kompleks, Essay, Menjodohkan.',
            '3. PG: isi Opsi A–D, Kunci Jawaban satu huruf (mis. B).',
            '4. PG Kompleks: isi Opsi A–D, Kunci Jawaban beberapa huruf dipisah koma (mis. A,C).',
            '5. Essay: Kunci Jawaban berisi pedoman jawaban untuk guru (boleh kosong). Essay dinilai guru di menu Koreksi Essay.',
            '6. Menjodohkan: isi Pasangan Menjodohkan, satu pasangan per baris (Alt+Enter) dengan format "Kiri = Kanan". Minimal 2, maksimal '.QuizQuestionForm::MAX_PAIRS.' pasangan.',
            '7. Poin boleh dikosongkan (otomatis 1). Mata pelajaran dipilih saat upload.',
            '8. File template resmi Kahoot (kahoot.com) juga bisa langsung di-upload: dibaca sebagai pilihan ganda, kunci 1–4 = A–D. Kolom Time limit tidak dipakai (waktu per soal diatur saat membuat kuis).',
        ]));
        $guide->getStyle('A1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(110);
        $spreadsheet->setActiveSheetIndex(0);

        return $this->exporter->downloadSpreadsheet($spreadsheet, 'template-import-soal.xlsx');
    }

    /**
     * Baca & validasi semua baris. Bila ada satu baris salah, tidak ada soal
     * yang disimpan dan semua kesalahan dilaporkan per nomor baris.
     *
     * @return list<array<string, mixed>> isian soal siap untuk QuizQuestionForm::attributes()
     *
     * @throws ValidationException
     */
    public function parse(UploadedFile $file): array
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getSheet(0);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Pastikan file berformat .xlsx dari template yang disediakan.']);
        }

        $kahootColumns = $this->kahootColumns($sheet);
        if ($kahootColumns === null) {
            $this->assertHeaders($sheet);
        }

        $items = [];
        $rules = [];
        $messages = [];

        for ($row = $kahootColumns['first_row'] ?? self::FIRST_DATA_ROW; $row <= $sheet->getHighestDataRow(); $row++) {
            $cells = $kahootColumns
                ? $this->kahootCells($sheet, $kahootColumns, $row)
                : array_map(fn (string $column) => trim((string) $sheet->getCell($column.$row)->getCalculatedValue()), range('A', 'J'));

            if (implode('', $cells) === '') {
                continue;
            }

            $item = $this->itemFromCells($cells);
            $items[$row] = $item;
            $rules += QuizQuestionForm::rules("rows.{$row}", $item['type']);
            $messages += QuizQuestionForm::messages("rows.{$row}", "Baris {$row}");
            $messages["rows.{$row}.type.in"] = "Baris {$row}: jenis soal \"{$cells[0]}\" tidak dikenal. Gunakan PG, PG Kompleks, Essay, atau Menjodohkan.";
        }

        if ($items === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi soal. Isi soal mulai baris ke-'.self::FIRST_DATA_ROW.'.']);
        }

        $validator = Validator::make(['rows' => $items], $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['file' => array_values(array_unique($validator->errors()->all()))]);
        }

        return array_values($items);
    }

    /**
     * @param  list<string>  $cells
     * @return array<string, mixed>
     */
    private function itemFromCells(array $cells): array
    {
        [$type, $question, $optionA, $optionB, $optionC, $optionD, $key, $pairs, $points, $explanation] = $cells;
        $type = self::TYPE_ALIASES[mb_strtolower($type)] ?? ($type === '' ? null : $type);

        return [
            'type' => $type,
            'question' => $question,
            'points' => $points === '' ? null : $points,
            'explanation' => $explanation,
            'options' => ['A' => $optionA, 'B' => $optionB, 'C' => $optionC, 'D' => $optionD],
            'correct_answer' => collect(preg_split('/[,;\s]+/', strtoupper($key)) ?: [])->filter()->values()->all(),
            'answer_key' => $key,
            'pairs' => collect(preg_split('/\r\n|\r|\n|;/', $pairs) ?: [])
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->map(function (string $line) {
                    [$left, $right] = array_pad(array_map('trim', explode('=', $line, 2)), 2, '');

                    return ['left' => $left, 'right' => $right];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * Cari baris judul template Kahoot ("Question", "Answer 1", ...) di 20
     * baris pertama. Null bila file bukan template Kahoot.
     *
     * @return array{first_row: int, question: string, answers: list<string>, correct: string}|null
     */
    private function kahootColumns(Worksheet $sheet): ?array
    {
        $lastColumn = min(20, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));

        for ($row = 1; $row <= min(20, $sheet->getHighestDataRow()); $row++) {
            $headers = [];
            for ($index = 1; $index <= $lastColumn; $index++) {
                $letter = Coordinate::stringFromColumnIndex($index);
                $headers[$letter] = mb_strtolower(trim((string) $sheet->getCell($letter.$row)->getValue()));
            }

            $find = fn (string $prefix) => array_key_first(array_filter($headers, fn (string $text) => str_starts_with($text, $prefix)));
            $question = $find('question');
            $answers = array_map(fn (int $number) => $find("answer {$number}"), [1, 2, 3, 4]);
            $correct = $find('correct answer');

            if ($question && $correct && ! in_array(null, $answers, true)) {
                return ['first_row' => $row + 1, 'question' => $question, 'answers' => $answers, 'correct' => $correct];
            }
        }

        return null;
    }

    /**
     * Ubah satu baris Kahoot ke urutan kolom template kita. Kunci 1–4 → A–D;
     * lebih dari satu kunci menjadi pilihan ganda kompleks.
     *
     * @param  array{first_row: int, question: string, answers: list<string>, correct: string}  $columns
     * @return list<string>
     */
    private function kahootCells(Worksheet $sheet, array $columns, int $row): array
    {
        $text = fn (string $column) => trim((string) $sheet->getCell($column.$row)->getCalculatedValue());
        $question = $text($columns['question']);
        $answers = array_map($text, $columns['answers']);

        if ($question === '' && implode('', $answers) === '') {
            return array_fill(0, 10, '');
        }

        $keys = collect(preg_split('/[,;\s]+/', $text($columns['correct'])) ?: [])
            ->filter(fn (string $number) => in_array($number, ['1', '2', '3', '4'], true))
            ->map(fn (string $number) => QuizQuestion::OPTION_KEYS[(int) $number - 1])
            ->unique()->values();

        return [$keys->count() > 1 ? 'PG Kompleks' : 'PG', $question, ...$answers, $keys->implode(','), '', '', ''];
    }

    private function assertHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $index => $header) {
            $column = chr(ord('A') + $index);
            if (strcasecmp(trim((string) $sheet->getCell($column.'1')->getValue()), $header) !== 0) {
                throw ValidationException::withMessages(['file' => "Format kolom tidak sesuai (sel {$column}1 seharusnya \"{$header}\"). Gunakan template dari tombol Unduh Template."]);
            }
        }
    }
}
