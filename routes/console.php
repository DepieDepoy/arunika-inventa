<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| SUBSCRIPTION REMINDERS
|--------------------------------------------------------------------------
| H-7 dan H-1 sebelum upgrade / add-on / paket berakhir.
| Server produksi perlu cron:  * * * * * php /path/artisan schedule:run
*/

Illuminate\Support\Facades\Schedule::command('subscription:reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Hapus token login mobile yang sudah kedaluwarsa (lihat config/sanctum.php).
Illuminate\Support\Facades\Schedule::command('sanctum:prune-expired --hours=24')
    ->dailyAt('02:00')
    ->withoutOverlapping();
