<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UserImportTemplateExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithColumnWidths
{
    /**
     * Header Excel
     */
    public function headings(): array
    {
        return [
            'No',
            'Name',
            'ID Person (NIK)',
            'Email',
            'Phone',
        ];
    }

    /**
     * Contoh data
     */
    public function array(): array
    {
        return [
            [
                '1',
                'Budi Santoso',
                '3201234567890001',
                'budi.santoso@example.com',
                '081234567890',
            ],

            [
                '2',
                'Andi Pratama',
                '3201234567890002',
                'andi.pratama@example.com',
                '081298765432',
            ],
        ];
    }

    /**
     * Styling Excel
     */
    public function styles(Worksheet $sheet): array
    {
        // Header
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4472C4',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Contoh data
        $sheet->getStyle('A2:E3')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        // Tinggi header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Freeze header
        $sheet->freezePane('A2');

        /*
        |--------------------------------------------------------------------------
        | Format NIK dan Phone sebagai text
        |--------------------------------------------------------------------------
        |
        | Supaya angka 0 di depan nomor telepon tidak hilang
        | dan NIK tidak dianggap sebagai angka oleh Excel.
        |
        */

        $sheet->getStyle('C2:C1000')
            ->getNumberFormat()
            ->setFormatCode('@');

        $sheet->getStyle('E2:E1000')
            ->getNumberFormat()
            ->setFormatCode('@');

        return [];
    }

    /**
     * Lebar kolom
     */
    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 30,
            'C' => 22,
            'D' => 35,
            'E' => 20,
        ];
    }
}