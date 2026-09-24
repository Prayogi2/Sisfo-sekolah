<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExportService
{
    public function xlsx(string $filename, array $headers, iterable $rows)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers], null, 'A1');
        $rowNumber = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([$row], null, 'A'.$rowNumber++);
        }

        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= $highestColumn; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Unduh spreadsheet yang sudah disusun sendiri (mis. dengan sel gabungan).
     */
    public function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename)
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'nurfa-report-');
        (new Xlsx($spreadsheet))->save($temporaryFile);

        return response()->download($temporaryFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function pdf(string $view, array $data, string $filename)
    {
        $options = new Options;
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data + ['pdf' => true])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
