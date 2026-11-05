<?php

/*
|--------------------------------------------------------------------------
| Aplikasi Mobile (Flutter)
|--------------------------------------------------------------------------
|
| Dibaca oleh GET /api/v1/app-info. Naikkan MOBILE_MIN_APP_VERSION bila
| ada perubahan API yang tidak kompatibel, agar aplikasi lama diminta update.
|
*/

return [
    'min_app_version' => env('MOBILE_MIN_APP_VERSION', '1.0.0'),
    'latest_app_version' => env('MOBILE_LATEST_APP_VERSION', '1.0.0'),

    'store_url' => [
        'android' => env('MOBILE_PLAY_STORE_URL'),
        'ios' => env('MOBILE_APP_STORE_URL'),
    ],

    'support_email' => env('MOBILE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
