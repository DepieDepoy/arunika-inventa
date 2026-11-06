<?php

/*
|--------------------------------------------------------------------------
| Analitik pengunjung (landing, daftar, login)
|--------------------------------------------------------------------------
|
| Dicatat langsung dari server ke tabel analytics_hits, tanpa layanan luar.
| Dashboard-nya ada di Backoffice > Analitik (khusus Super Admin).
|
*/

return [

    'enabled' => env('ANALYTICS_ENABLED', true),

    // IP disimpan selama N hari, lalu dikosongkan otomatis (data lain tetap untuk statistik).
    'ip_retention_days' => (int) env('ANALYTICS_IP_RETENTION_DAYS', 60),

    // Baris kunjungan dihapus seluruhnya setelah N hari.
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 400),

    // Cookie penanda pengunjung (first-party, tanpa data pribadi).
    'cookie' => 'vs_vid',
    'cookie_days' => 365,

];
