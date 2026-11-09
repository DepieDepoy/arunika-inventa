<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportHistoryController;

// Halaman publik: landing, kebijakan privasi, syarat layanan, hapus akun.
require __DIR__.'/landing.php';


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

// Alamat lama /profile (bawaan Breeze) diarahkan ke profil dashboard.
// Nama rute profile.update dll. dipakai routes/dashboard/profile.php;
// nama ganda membuat `php artisan optimize` (route:cache) gagal.
Route::redirect('/profile', '/dashboard/profile')->name('profile.edit');


/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';


/*
|--------------------------------------------------------------------------
| DASHBOARD MODULES
|--------------------------------------------------------------------------
*/

require __DIR__.'/dashboard/home.php';
require __DIR__.'/dashboard/role.php';
require __DIR__.'/dashboard/user.php';
require __DIR__.'/dashboard/category.php';
require __DIR__.'/dashboard/subcategory.php';
require __DIR__.'/dashboard/vendor.php';
require __DIR__.'/dashboard/asset.php';
require __DIR__.'/dashboard/maintenance.php';
require __DIR__.'/dashboard/subscription.php';
require __DIR__.'/backoffice.php';
require __DIR__.'/dashboard/profile.php';
require __DIR__.'/dashboard/calendar.php';
require __DIR__.'/dashboard/notification.php';
require __DIR__.'/dashboard/audit.php';
require __DIR__.'/dashboard/report.php';
require __DIR__.'/dashboard/company.php';
require __DIR__.'/dashboard/branch.php';
require __DIR__.'/dashboard/asset-audit.php';


/*
|--------------------------------------------------------------------------
| IMPORT HISTORY
|--------------------------------------------------------------------------
*/

Route::get('/dashboard/import-history', [ImportHistoryController::class, 'index'])
    ->name('import.history');

Route::get('/dashboard/import-history/progress', [ImportHistoryController::class, 'progress'])
    ->name('import.history.progress');

Route::get('/dashboard/import-history-detail/{id}/errors', [ImportHistoryController::class, 'detailErrors'])
    ->name('import.history.detail.errors');

Route::get('/dashboard/import-history-detail/{id}', [ImportHistoryController::class, 'detail'])
    ->name('import.history.detail');
