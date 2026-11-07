<?php

/*
|--------------------------------------------------------------------------
| Push notification
|--------------------------------------------------------------------------
|
| Web (Chrome / Edge / Firefox / Safari): Web Push standar dengan kunci VAPID.
|   Gratis, tanpa akun pihak ketiga. Buat kunci: php artisan vasetra:vapid-keys
|
| Mobile (Android / iOS): Firebase Cloud Messaging (FCM HTTP v1).
|   Isi FIREBASE_CREDENTIALS dengan path file service account JSON dari
|   Firebase Console > Project settings > Service accounts.
|   Selama kosong, push mobile dilewati (notifikasi tetap tersimpan di lonceng).
|
*/

return [

    'enabled' => env('PUSH_ENABLED', true),

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:info@vasetra.com'),
    ],

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
