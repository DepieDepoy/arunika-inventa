<?php

namespace App\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pembuat file Excel (.xlsx) bergaya seragam untuk semua export & laporan Vasetra.
 *
 * Kolom: ['key' => 'asset_name', 'label' => 'Nama aset', 'type' => 'text|number|money|date|datetime|percent', 'width' => 24]
 * Baris: array asosiatif / objek, atau closure ($row) => nilai per kolom lewat 'value'.
 */
class SpreadsheetExporter
{
    public const NAVY = '0B2447';
    public const BLUE = '1F5FBF';
    public const LINE = 'DCE3EC';
    public const ZEBRA = 'F6F8FB';

    protected Spreadsheet $book;
    protected bool $first = true;

    public function __construct(protected ?string $companyName = null, protected ?string $exportedBy = null)
    {
        $this->book = new Spreadsheet();
        $this->book->getProperties()
            ->setCreator('Vasetra')
            ->setCompany((string) $companyName)
            ->setTitle('Vasetra Export');
        $this->book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
    }

    public static function forCurrentUser(): self
    {
        $user = auth()->user();

        return new self($user?->company?->company_name, $user?->name);
    }

    /**
     * Tambah sheet tabel. Mengembalikan worksheet untuk penyesuaian lanjutan.
     */
    public function table(string $sheetTitle, string $heading, array $columns, iterable $rows, ?string $subtitle = null): Worksheet
    {
        $sheet = $this->newSheet($sheetTitle);
        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($columns)));

        $r = $this->header($sheet, $heading, $subtitle, $lastCol);

        // Judul kolom.
        foreach (array_values($columns) as $i => $col) {
            $sheet->setCellValue([$i + 1, $r], $col['label']);
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($col['width'] ?? 18);
        }
        $headRow = $r;
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // Data.
        $r++;
        $first = $r;
        foreach ($rows as $row) {
            foreach (array_values($columns) as $i => $col) {
                $this->writeCell($sheet, $i + 1, $r, $this->valueOf($row, $col), $col['type'] ?? 'text');
            }
            $r++;
        }
        $last = $r - 1;

        if ($last >= $first) {
            foreach (array_values($columns) as $i => $col) {
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $format = match ($col['type'] ?? 'text') {
                    'money' => '"Rp"#,##0;-"Rp"#,##0;"Rp"0',
                    'number' => '#,##0',
                    'decimal' => '#,##0.0',
                    'percent' => '0.0"%"',
                    'date' => 'dd/mm/yyyy',
                    'datetime' => 'dd/mm/yyyy hh:mm',
                    default => null,
                };
                if ($format) {
                    $sheet->getStyle("{$letter}{$first}:{$letter}{$last}")->getNumberFormat()->setFormatCode($format);
                }
                if (in_array($col['type'] ?? 'text', ['money', 'number', 'decimal', 'percent'], true)) {
                    $sheet->getStyle("{$letter}{$first}:{$letter}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }

            $sheet->getStyle("A{$headRow}:{$lastCol}{$last}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::LINE);
            $sheet->setAutoFilter("A{$headRow}:{$lastCol}{$last}");
        } else {
            $sheet->setCellValue("A{$r}", 'Tidak ada data.');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setRGB('8592A6');
        }

        $sheet->freezePane('A' . ($headRow + 1));

        return $sheet;
    }

    /**
     * Sheet ringkasan: pasangan label => nilai, dikelompokkan per judul bagian.
     *
     * @param array<string, array<int, array{0:string,1:mixed,2?:string}>> $sections
     */
    public function summary(string $sheetTitle, string $heading, array $sections, ?string $subtitle = null): Worksheet
    {
        $sheet = $this->newSheet($sheetTitle);
        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(30);

        $r = $this->header($sheet, $heading, $subtitle, 'C');

        foreach ($sections as $title => $items) {
            $sheet->setCellValue("A{$r}", $title);
            $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BLUE]],
            ]);
            $r++;
            foreach ($items as $item) {
                [$label, $value] = $item;
                $type = $item[2] ?? 'text';
                $note = $item[3] ?? null;
                $sheet->setCellValue("A{$r}", $label);
                $this->writeCell($sheet, 2, $r, $value, $type ?? 'text');
                $fmt = match ($type ?? 'text') {
                    'money' => '"Rp"#,##0',
                    'number' => '#,##0',
                    'percent' => '0.0"%"',
                    'decimal' => '#,##0.0',
                    default => null,
                };
                if ($fmt) {
                    $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($fmt);
                }
                $sheet->getStyle("B{$r}")->getFont()->setBold(true);
                $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                if ($note) {
                    $sheet->setCellValue("C{$r}", $note);
                    $sheet->getStyle("C{$r}")->getFont()->getColor()->setRGB('8592A6');
                }
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:C{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ZEBRA);
                }
                $r++;
            }
            $r++;
        }

        return $sheet;
    }

    public function spreadsheet(): Spreadsheet
    {
        return $this->book;
    }

    /**
     * Unduh sebagai .xlsx (streaming, tanpa file sementara di server).
     */
    public function download(string $filename): StreamedResponse
    {
        $this->book->setActiveSheetIndex(0);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        $book = $this->book;

        return response()->streamDownload(function () use ($book) {
            // Buang output liar yang masih di buffer (mis. baris kosong sebelum "<?php"
            // di salah satu file PHP) agar file .xlsx tidak rusak.
            if (!app()->runningInConsole()) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
            }
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /* ------------------------------------------------------------------ */

    protected function newSheet(string $title): Worksheet
    {
        if ($this->first) {
            $sheet = $this->book->getActiveSheet();
            $this->first = false;
        } else {
            $sheet = $this->book->createSheet();
        }

        // Nama sheet maks 31 karakter, tanpa karakter terlarang.
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $title), 0, 31));
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        return $sheet;
    }

    /**
     * Kop: nama perusahaan, judul, keterangan ekspor. Mengembalikan baris berikutnya.
     */
    protected function header(Worksheet $sheet, string $heading, ?string $subtitle, string $lastCol): int
    {
        $sheet->setCellValue('A1', $this->companyName ?: 'Vasetra');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::NAVY);

        $sheet->setCellValue('A2', $heading);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::BLUE);

        $meta = trim(($subtitle ? $subtitle . ' · ' : '') . 'Diekspor ' . now()->locale('id')->translatedFormat('d F Y H:i')
            . ($this->exportedBy ? ' oleh ' . $this->exportedBy : '') . ' · Vasetra Asset Lifecycle Platform');
        $sheet->setCellValue('A3', $meta);
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('8592A6');

        foreach ([1, 2, 3] as $row) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        }

        return 5;
    }

    protected function valueOf(mixed $row, array $col): mixed
    {
        if (isset($col['value']) && is_callable($col['value'])) {
            return ($col['value'])($row);
        }

        return data_get($row, $col['key']);
    }

    protected function writeCell(Worksheet $sheet, int $col, int $row, mixed $value, string $type): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (in_array($type, ['date', 'datetime'], true)) {
            $date = $value instanceof CarbonInterface ? $value : rescue(fn () => Carbon::parse($value), null, false);
            if ($date) {
                $sheet->setCellValue([$col, $row], \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date->copy()->setTimezone(config('app.timezone'))->toDateTimeString()));

                return;
            }
        }

        if (in_array($type, ['money', 'number', 'decimal', 'percent'], true) && is_numeric($value)) {
            $sheet->setCellValue([$col, $row], (float) $value);

            return;
        }

        // Teks disimpan eksplisit sebagai string: tidak pernah dieksekusi sebagai formula
        // (cegah formula injection) dan angka berawalan 0 (NIK / no. HP) tetap utuh.
        $sheet->setCellValueExplicit([$col, $row], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
}
