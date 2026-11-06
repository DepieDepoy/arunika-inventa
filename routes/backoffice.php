<?php

use App\Http\Controllers\Backoffice\ActivityController;
use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\CmsController;
use App\Http\Controllers\Backoffice\PlanController;
use App\Http\Controllers\Backoffice\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Vasetra (pegawai Vasetra)
|--------------------------------------------------------------------------
|
| Login & akun terpisah dari pelanggan (guard "backoffice", tabel backoffice_admins).
| Alamat diatur lewat BACKOFFICE_PATH (default: /backoffice).
|
*/

Route::prefix(config('backoffice.path', 'backoffice'))
    ->name('backoffice.')
    ->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.attempt');

        Route::middleware('backoffice')->group(function () {
            Route::get('/', fn () => redirect()->route('backoffice.cms.index'))->name('home');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
            Route::post('/password', [AuthController::class, 'updatePassword'])->name('password.update');

            Route::get('/cms', [CmsController::class, 'index'])->name('cms.index');
            Route::get('/cms/{key}', [CmsController::class, 'edit'])->where('key', '[a-z_]+')->name('cms.edit');
            Route::post('/cms/{key}', [CmsController::class, 'update'])->where('key', '[a-z_]+')->name('cms.update');
            Route::post('/cms/{key}/reset', [CmsController::class, 'reset'])->where('key', '[a-z_]+')->name('cms.reset');

            // Khusus Super Admin.
            Route::middleware('backoffice:super')->group(function () {
                Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
                Route::post('/plans', [PlanController::class, 'update'])->name('plans.update');

                Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
                Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
                Route::post('/staff/{staff}', [StaffController::class, 'update'])->whereNumber('staff')->name('staff.update');
                Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->whereNumber('staff')->name('staff.reset');

                Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
            });
        });
    });
