
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\CalendarController;

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])
            ->name('calendar.index');

        Route::post('/calendar/events', [CalendarController::class, 'store'])
            ->name('calendar.events.store');

        Route::put('/calendar/events/{id}', [CalendarController::class, 'update'])
            ->name('calendar.events.update');

        Route::delete('/calendar/events/{id}', [CalendarController::class, 'destroy'])
            ->name('calendar.events.destroy');
    });