<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class CodeHelper
{
    public static function generate(string $name, string $modelClass, string $codeColumn, int $companyId): string
    {
        // Bersihkan nama
        $name = trim($name);

        // Ambil kata-kata
        $words = preg_split('/\s+/', $name);

        // Buat code dari huruf awal setiap kata
        if (count($words) > 1) {

            $code = '';

            foreach ($words as $word) {
                $code .= Str::upper(Str::substr($word, 0, 1));
            }

        } else {

            // Kalau hanya satu kata, ambil maksimal 3 huruf
            $code = Str::upper(Str::substr($name, 0, 3));
        }

        // Bersihkan karakter selain huruf/angka
        $code = preg_replace('/[^A-Z0-9]/', '', $code);

        // Minimal fallback
        if (empty($code)) {
            $code = 'CAT';
        }

        $originalCode = $code;
        $counter = 1;

        // PENTING:
        // withTrashed() membuat data yang sudah soft-delete
        // tetap dianggap pernah menggunakan code tersebut.
        while (
            $modelClass::withTrashed()
                ->where('company_id', $companyId)
                ->where($codeColumn, $code)
                ->exists()
        ) {

            $code = $originalCode . str_pad(
                $counter,
                2,
                '0',
                STR_PAD_LEFT
            );

            $counter++;
        }

        return $code;
    }
}