<?php

namespace App\Helpers;

class CompanyHelper
{
    /**
     * Generate company code.
     */
    public static function generateCode(string $companyName): string
    {
        // Trim spasi depan & belakang
        $companyName = trim($companyName);

        // Huruf kecil
        $companyName = strtolower($companyName);

        // Semua karakter selain huruf & angka diganti spasi
        $companyName = preg_replace('/[^a-z0-9]+/', ' ', $companyName);

        // Hilangkan spasi berlebih
        $companyName = preg_replace('/\s+/', ' ', $companyName);

        // Ganti spasi menjadi underscore
        $companyName = str_replace(' ', '_', $companyName);

        return $companyName;
    }
}