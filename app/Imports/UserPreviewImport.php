<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class UserPreviewImport implements ToArray
{
    public function array(array $array): void
    {
        // Data digunakan langsung oleh Excel::toArray()
    }
}