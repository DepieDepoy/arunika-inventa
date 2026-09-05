<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ExcelPreviewService
{
    /**
     * Baca preview Excel tanpa memuat seluruh data ke memory.
     *
     * Mendukung:
     * - XLSX
     * - XLS
     *
     * @return array{
     *     data: array,
     *     totalRows: int,
     *     previewRows: int
     * }
     */
    public function preview(
        string $filePath,
        int $previewLimit = 100
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Buat reader otomatis berdasarkan extension/file
        |--------------------------------------------------------------------------
        */

        $reader = IOFactory::createReaderForFile($filePath);

        /*
        |--------------------------------------------------------------------------
        | Hanya membaca value
        |--------------------------------------------------------------------------
        */

        $reader->setReadDataOnly(true);

        /*
        |--------------------------------------------------------------------------
        | Ambil informasi worksheet
        |--------------------------------------------------------------------------
        */

        $sheetInfo = $reader->listWorksheetInfo($filePath);

        if (empty($sheetInfo)) {
            return [
                'data' => [],
                'totalRows' => 0,
                'previewRows' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Gunakan worksheet pertama
        |--------------------------------------------------------------------------
        */

        $worksheetInfo = $sheetInfo[0];

        $highestRow = (int) (
            $worksheetInfo['totalRows'] ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Filter:
        |
        | Row 1     = Header
        | Row 2-101 = maksimal 100 data
        |--------------------------------------------------------------------------
        */

        $reader->setReadFilter(
            new class($previewLimit) implements IReadFilter {

                protected int $startRow = 1;

                protected int $endRow;

                public function __construct(
                    int $previewLimit
                ) {
                    $this->endRow =
                        $this->startRow +
                        $previewLimit;
                }

                public function readCell(
                    $column,
                    $row,
                    $worksheetName = ''
                ): bool {

                    return $row >= $this->startRow
                        && $row <= $this->endRow;
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Load hanya row preview
        |--------------------------------------------------------------------------
        */

        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getSheet(0);

        /*
        |--------------------------------------------------------------------------
        | Ambil data sebagai array numerik
        |--------------------------------------------------------------------------
        |
        | Ini penting karena Blade existing menggunakan:
        |
        | $row[1] = Name
        | $row[2] = NIK
        | $row[3] = Email
        | $row[4] = Phone
        |
        |--------------------------------------------------------------------------
        */

        $data = $worksheet->toArray(
            null,
            true,
            true,
            false
        );

        /*
        |--------------------------------------------------------------------------
        | Bersihkan row kosong
        |--------------------------------------------------------------------------
        */

        $header = $data[0] ?? [];

        $rows = array_slice(
            $data,
            1
        );

        $rows = array_values(
            array_filter(
                $rows,
                function ($row) {

                    return !empty(
                        trim(
                            (string) (
                                $row[1] ?? ''
                            )
                        )
                    );
                }
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Preview maksimal 100 row
        |--------------------------------------------------------------------------
        */

        $previewRowsData = array_slice(
            $rows,
            0,
            $previewLimit
        );

        /*
        |--------------------------------------------------------------------------
        | Total row
        |--------------------------------------------------------------------------
        |
        | listWorksheetInfo() memberikan jumlah row worksheet
        | tanpa harus memuat seluruh worksheet.
        |
        | Dikurangi 1 karena row pertama adalah header.
        |--------------------------------------------------------------------------
        */

        $totalRows = max(
            $highestRow - 1,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | Jika total worksheet lebih kecil dari data preview,
        | gunakan jumlah data aktual.
        |--------------------------------------------------------------------------
        */

        if ($totalRows < count($rows)) {
            $totalRows = count($rows);
        }

        /*
        |--------------------------------------------------------------------------
        | Bebaskan memory
        |--------------------------------------------------------------------------
        */

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'data' => array_merge(
                [$header],
                $previewRowsData
            ),

            'totalRows' => $totalRows,

            'previewRows' => count(
                $previewRowsData
            ),
        ];
    }
}