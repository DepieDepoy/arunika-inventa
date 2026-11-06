<?php

/*
|--------------------------------------------------------------------------
| Backoffice Vasetra
|--------------------------------------------------------------------------
|
| Panel khusus pegawai Vasetra (CMS landing page, paket & harga).
| Login & akun terpisah dari pelanggan (tabel backoffice_admins, guard "backoffice").
|
*/

return [
    // Alamat backoffice, mis. https://vasetra.com/backoffice
    'path' => trim(env('BACKOFFICE_PATH', 'backoffice'), '/'),

    // Akun pertama (dipakai BackofficeAdminSeeder).
    'seed' => [
        'name' => env('BACKOFFICE_ADMIN_NAME', 'Admin Vasetra'),
        'email' => env('BACKOFFICE_ADMIN_EMAIL'),
        'password' => env('BACKOFFICE_ADMIN_PASSWORD'),
    ],

    // Batas gagal login sebelum dikunci sementara.
    'max_login_attempts' => 5,
    'lockout_minutes' => 15,
];
