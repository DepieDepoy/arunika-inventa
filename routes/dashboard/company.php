<?php

use App\Http\Controllers\Dashboard\CompanyProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PROFIL PERUSAHAAN (khusus Administrator)
|--------------------------------------------------------------------------
| Nama, alamat, dan logo perusahaan untuk laporan PDF / PowerPoint.
*/

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access', 'administrator'])
    ->group(function () {
        Route::get('/company', [CompanyProfileController::class, 'edit'])->name('company.edit');
        Route::post('/company', [CompanyProfileController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('company.update');
    });
