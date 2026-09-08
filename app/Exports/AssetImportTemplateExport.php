<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class AssetImportTemplateExport implements
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
            'Asset Name',
            'Category',
            'Sub Category',
            'Vendor',
            'Vendor Address',
            'ID Person (NIK)',
            'Brand',
            'Model',
            'Serial Number',
            'Description',
            'Condition',
            'Purchase Date',
            'Purchase Price',
            'Purchase Invoice',
            'Depreciation Method',
            'Useful Life',
            'Residual Value',
            'Depreciation Start Date',
            'Warranty Start',
            'Warranty End',
            'Warranty Note',
            'Maintenance Required',
            'Maintenance Type',
            'Maintenance Trigger',
            'Maintenance Interval',
            'Maintenance Interval Unit',
            'Maintenance Start Date',
            'Location',
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
                'Laptop Dell Latitude',
                'IT Equipment',
                'Laptop',
                'PT Contoh Vendor',
                'Jl. Contoh No. 123, Jakarta',
                'CGKxxx',
                'Dell',
                'Latitude 5440',
                'SN123456789',
                'Laptop untuk kebutuhan operasional',
                'new',
                '2026-09-03',
                12500000,
                'INV-2026-001',
                'straight_line',
                5,
                1000000,
                '2026-09-03',
                '2026-09-03',
                '2027-09-03',
                'Garansi resmi vendor',
                'no',
                '',
                '',
                '',
                '',
                '',
                'Jakarta',
            ],

            [
                '2',
                'AC Split 1/2 PK',
                'Electrical',
                'Air Conditioner',
                'PT Contoh Vendor',
                'Jl. Contoh No. 123, Jakarta',
                '',
                'Daikin',
                'FTKC15',
                'AC123456789',
                'AC ruang meeting',
                'new',
                '2026-09-03',
                4000000,
                'INV-2026-002',
                'straight_line',
                5,
                500000,
                '2026-09-03',
                '2026-09-03',
                '2027-09-03',
                'Garansi resmi vendor',
                'yes',
                'preventive',
                'calendar',
                6,
                'month',
                '2026-09-03',
                'Jakarta',
            ],
        ];
    }

    /**
     * Styling Excel
     */
    public function styles(Worksheet $sheet): array
    {
        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:AC1')->applyFromArray([
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

        /*
        |--------------------------------------------------------------------------
        | Contoh Data
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A2:AC3')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | NIK / ID Person
        |--------------------------------------------------------------------------
        |
        | Kolom G dibuat Text agar:
        |
        | 001234
        |
        | tidak berubah menjadi:
        |
        | 1234
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Tinggi Header
        |--------------------------------------------------------------------------
        */

        $sheet->getRowDimension(1)->setRowHeight(25);

        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A2');

        /*
        |--------------------------------------------------------------------------
        | Dropdown Validation
        |--------------------------------------------------------------------------
        */

        // Condition - kolom L
        $this->addDropdown(
            $sheet,
            'L2:L1000',
            '"new,used"'
        );

        // Depreciation Method - kolom P
        $this->addDropdown(
            $sheet,
            'P2:P1000',
            '"straight_line"'
        );

        // Maintenance Required - kolom W
        $this->addDropdown(
            $sheet,
            'W2:W1000',
            '"yes,no"'
        );

        // Maintenance Type - kolom X
        $this->addDropdown(
            $sheet,
            'X2:X1000',
            '"preventive,corrective"'
        );

        // Maintenance Trigger - kolom Y
        $this->addDropdown(
            $sheet,
            'Y2:Y1000',
            '"calendar,usage"'
        );

        // Maintenance Interval Unit - kolom AA
        $this->addDropdown(
            $sheet,
            'AA2:AA1000',
            '"day,month,year"'
        );

        return [];
    }

    /**
     * Membuat dropdown Excel
     */
    private function addDropdown(
        Worksheet $sheet,
        string $range,
        string $formula
    ): void {
        $validation = new DataValidation();

        $validation->setType(
            DataValidation::TYPE_LIST
        );

        $validation->setErrorStyle(
            DataValidation::STYLE_STOP
        );

        $validation->setAllowBlank(true);

        $validation->setShowInputMessage(true);

        $validation->setShowErrorMessage(true);

        $validation->setShowDropDown(true);

        $validation->setErrorTitle(
            'Input tidak valid'
        );

        $validation->setError(
            'Silakan pilih nilai dari dropdown yang tersedia.'
        );

        $validation->setPromptTitle(
            'Pilih nilai'
        );

        $validation->setPrompt(
            'Silakan pilih salah satu pilihan dari dropdown.'
        );

        $validation->setFormula1($formula);

        $sheet->setDataValidation(
            $range,
            $validation
        );
    }

    /**
     * Lebar kolom
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 20,
            'E' => 25,
            'F' => 55,
            'G' => 18,
            'H' => 20,
            'I' => 22,
            'J' => 35,
            'K' => 35,
            'L' => 15,
            'M' => 18,
            'N' => 20,
            'O' => 22,
            'P' => 20,
            'Q' => 18,
            'R' => 20,
            'S' => 22,
            'T' => 18,
            'U' => 18,
            'V' => 30,
            'W' => 22,
            'X' => 20,
            'Y' => 20,
            'Z' => 22,
            'AA' => 25,
            'AB' => 22,
            'AC' => 25,
        ];
    }
}