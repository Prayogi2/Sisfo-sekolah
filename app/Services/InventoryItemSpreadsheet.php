<?php

namespace App\Services;

use App\Enums\InventoryCategory;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Models\Classroom;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryItemSpreadsheet
{
    public function __construct(private ReportExportService $exporter) {}

    public function template(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Inventaris');
        $sheet->fromArray([
            ['Kategori', 'Nama Barang', 'Jumlah'],
            ['Mebelair', 'Meja Belajar Siswa', 24],
            ['Elektronik', 'Lampu', 4],
        ]);
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C3')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(12);

        $guide = $spreadsheet->createSheet()->setTitle('Petunjuk');
        $guide->fromArray(array_map(fn (string $line) => [$line], [
            'PETUNJUK IMPORT INVENTARIS',
            'Isi satu barang per baris pada sheet Inventaris. Baris contoh boleh diubah atau dihapus.',
            'Kolom wajib: Kategori, Nama Barang, Jumlah.',
            'Kategori harus memakai salah satu label berikut:',
            ...array_map(fn (InventoryCategory $category) => '- '.$category->label(), InventoryCategory::cases()),
            'Barang yang sudah tercatat dengan kategori sama tidak boleh diulang.',
            'Maksimal '.StoreInventoryItemRequest::MAX_ITEMS.' barang dalam satu file.',
        ]));
        $guide->getStyle('A1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(90);
        $spreadsheet->setActiveSheetIndex(0);

        return $this->exporter->downloadSpreadsheet($spreadsheet, 'template-inventaris-kelas.xlsx');
    }

    /**
     * @return list<array{category: string, name: string, quantity: int}>
     *
     * @throws ValidationException
     */
    public function parse(UploadedFile $file, Classroom $classroom): array
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getSheet(0);
        } catch (ReaderException) {
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Gunakan template inventaris berformat .xlsx.']);
        }

        $headers = array_map(
            fn (string $column) => mb_strtolower(trim((string) $sheet->getCell($column.'1')->getCalculatedValue())),
            ['A', 'B', 'C'],
        );

        if ($headers !== ['kategori', 'nama barang', 'jumlah']) {
            throw ValidationException::withMessages(['file' => 'Format kolom tidak sesuai. Unduh dan gunakan template inventaris.']);
        }

        $categoryValues = [];
        foreach (InventoryCategory::cases() as $category) {
            $categoryValues[mb_strtolower($category->value)] = $category->value;
            $categoryValues[mb_strtolower($category->label())] = $category->value;
        }

        $items = [];
        $rowErrors = [];
        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $cells = array_map(
                fn (string $column) => trim((string) $sheet->getCell($column.$row)->getCalculatedValue()),
                ['A', 'B', 'C'],
            );

            if ($cells === ['', '', '']) {
                continue;
            }

            $category = $categoryValues[mb_strtolower($cells[0])] ?? null;
            if ($category === null) {
                $rowErrors[] = "Baris {$row}: kategori \"{$cells[0]}\" tidak dikenal.";

                continue;
            }

            $items[$row] = [
                'category' => $category,
                'name' => $cells[1],
                'quantity' => $cells[2],
            ];
        }

        if ($rowErrors !== []) {
            throw ValidationException::withMessages(['file' => $rowErrors]);
        }

        if ($items === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi barang inventaris.']);
        }

        $validator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'max:'.StoreInventoryItemRequest::MAX_ITEMS],
            'items.*.category' => ['required', Rule::enum(InventoryCategory::class)],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'items.max' => 'Maksimal '.StoreInventoryItemRequest::MAX_ITEMS.' barang sekali import.',
            'items.*.name.required' => 'Nama barang wajib diisi.',
            'items.*.name.max' => 'Nama barang maksimal 100 karakter.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah barang harus berupa angka bulat.',
            'items.*.quantity.min' => 'Jumlah barang tidak boleh negatif.',
            'items.*.quantity.max' => 'Jumlah barang maksimal 9999.',
        ]);
        $validator->after(function ($validator) use ($items, $classroom) {
            foreach (InventoryItemListValidator::duplicateErrors($items, $classroom) as $error) {
                $validator->errors()->add("items.{$error['index']}.name", $error['message']);
            }
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages(['file' => array_values(array_unique($validator->errors()->all()))]);
        }

        return array_values($validator->validated()['items']);
    }
}
