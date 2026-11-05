<?php

use App\Http\Controllers\Dashboard\AuditTrailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUDIT TRAIL
|--------------------------------------------------------------------------
|
| Hanya Administrator. Data dibatasi per PT di controller.
| Read-only: tidak ada route ubah / hapus.
|
*/

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access', 'administrator'])
    ->group(function () {

        Route::get('/audit-trail', [AuditTrailController::class, 'index'])
            ->name('audit-trail.index');

        Route::get('/audit-trail/data', [AuditTrailController::class, 'data'])
            ->name('audit-trail.data');

        Route::get('/audit-trail/{id}', [AuditTrailController::class, 'show'])
            ->name('audit-trail.show');
    });
