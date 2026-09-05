<?php

namespace App\Helpers;

class CompanyHelper
{
    public static function generateCode(string $companyName): string
    {
        // Trim spasi depan & belakang
        $companyName = trim($companyName);

        // Hapus semua karakter selain huruf & angka
        $companyName = preg_replace('/[^a-zA-Z0-9]+/', '', $companyName);

        // Ubah menjadi huruf besar
        return strtoupper($companyName);
    }
}