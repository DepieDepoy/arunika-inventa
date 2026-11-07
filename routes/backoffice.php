<?php

use App\Http\Controllers\Backoffice\ActivityController;
use App\Http\Controllers\Backoffice\AnalyticsController;
use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\CmsController;
use App\Http\Controllers\Backoffice\CustomerController;
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

        // Langkah 2: kode aplikasi authenticator (sebelum benar-benar login).
        Route::get('/login/verify', [AuthController::class, 'showChallenge'])->name('two-factor.challenge');
        Route::post('/login/verify', [AuthController::class, 'verifyChallenge'])->middleware('throttle:10,1')->name('two-factor.verify');

        Route::middleware('backoffice')->group(function () {
            Route::get('/', fn () => redirect()->route(
                auth('backoffice')->user()?->isSuperAdmin() ? 'backoffice.dashboard' : 'backoffice.cms.index'
            ))->name('home');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
            Route::post('/password', [AuthController::class, 'updatePassword'])->name('password.update');

            // Keamanan akun: verifikasi 2 langkah milik sendiri.
            Route::get('/security/two-factor', [\App\Http\Controllers\Backoffice\TwoFactorController::class, 'show'])->name('two-factor.show');
            Route::post('/security/two-factor', [\App\Http\Controllers\Backoffice\TwoFactorController::class, 'enable'])->middleware('throttle:10,1')->name('two-factor.enable');
            Route::post('/security/two-factor/disable', [\App\Http\Controllers\Backoffice\TwoFactorController::class, 'disable'])->middleware('throttle:10,1')->name('two-factor.disable');
            Route::post('/security/two-factor/codes', [\App\Http\Controllers\Backoffice\TwoFactorController::class, 'regenerate'])->middleware('throttle:10,1')->name('two-factor.codes');

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

                Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
                Route::get('/transactions', [\App\Http\Controllers\Backoffice\TransactionController::class, 'index'])->name('transactions.index');
                Route::get('/transactions/export', [\App\Http\Controllers\Backoffice\TransactionController::class, 'export'])->name('transactions.export');
                Route::get('/transactions/{transaction}/invoice', [\App\Http\Controllers\Backoffice\TransactionController::class, 'invoice'])->whereNumber('transaction')->name('transactions.invoice');
                Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
                Route::get('/customers/{company}', [CustomerController::class, 'show'])->whereNumber('company')->name('customers.show');
                Route::post('/customers/{company}/extend-trial', [CustomerController::class, 'extendTrial'])->whereNumber('company')->name('customers.extend-trial');
                Route::post('/customers/{company}/status', [CustomerController::class, 'toggleStatus'])->whereNumber('company')->name('customers.status');
                Route::post('/customers/{company}/refresh-storage', [CustomerController::class, 'refreshStorage'])->whereNumber('company')->name('customers.refresh-storage');

                // Pengaturan keamanan (kebijakan 2 langkah) & reset 2 langkah pegawai.
                Route::get('/settings/security', [\App\Http\Controllers\Backoffice\SecurityController::class, 'edit'])->name('settings.security');
                Route::post('/settings/security', [\App\Http\Controllers\Backoffice\SecurityController::class, 'update'])->name('settings.security.update');
                Route::post('/staff/{staff}/two-factor-reset', [\App\Http\Controllers\Backoffice\SecurityController::class, 'reset'])->whereNumber('staff')->name('staff.two-factor-reset');

                // Pengaturan: aplikasi mobile & Tera AI.
                Route::get('/settings/mobile', [\App\Http\Controllers\Backoffice\MobileAppController::class, 'edit'])->name('settings.mobile');
                Route::post('/settings/mobile', [\App\Http\Controllers\Backoffice\MobileAppController::class, 'update'])->name('settings.mobile.update');
                Route::post('/settings/push', [\App\Http\Controllers\Backoffice\MobileAppController::class, 'updatePush'])->name('settings.push.update');
                Route::get('/settings/tera', [\App\Http\Controllers\Backoffice\TeraController::class, 'edit'])->name('settings.tera');
                Route::post('/settings/tera', [\App\Http\Controllers\Backoffice\TeraController::class, 'update'])->name('settings.tera.update');
                Route::post('/settings/tera/test', [\App\Http\Controllers\Backoffice\TeraController::class, 'test'])->middleware('throttle:20,1')->name('settings.tera.test');
                Route::post('/customers/{company}/tera', [\App\Http\Controllers\Backoffice\TeraController::class, 'toggleCompany'])->whereNumber('company')->name('customers.tera');

                Route::get('/account-deletions',[\App\Http\Controllers\Backoffice\AccountDeletionController::class, 'index'])->name('account-deletions.index');
                Route::post('/account-deletions/{deletion}/process', [\App\Http\Controllers\Backoffice\AccountDeletionController::class, 'process'])->whereNumber('deletion')->name('account-deletions.process');
                Route::post('/account-deletions/{deletion}/reject', [\App\Http\Controllers\Backoffice\AccountDeletionController::class, 'reject'])->whereNumber('deletion')->name('account-deletions.reject');

                Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
                Route::post('/analytics/settings', [AnalyticsController::class, 'settings'])->name('analytics.settings');
            });
        });
    });
