<?php

/*
|--------------------------------------------------------------------------
| CORS (akses API dari browser, mis. Flutter web)
|--------------------------------------------------------------------------
| API mobile memakai Bearer token (bukan cookie), jadi aman dibuka untuk
| origin mana pun ('*'). Bila ingin dibatasi, isi CORS_ALLOWED_ORIGINS
| dengan daftar dipisah koma, mis. "https://app.vasetra.com".
| Aplikasi Android/iOS yang sudah di-compile tidak terpengaruh CORS.
*/

$origins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')))));

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins ?: ['*'],
    // Saat pengembangan, Flutter web lokal (port acak) selalu diizinkan.
    'allowed_origins_patterns' => env('APP_ENV') === 'local'
        ? ['#^http://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],
    'allowed_headers' => ['*'],
    // Agar Flutter web bisa membaca nama file saat unduh laporan/export.
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
