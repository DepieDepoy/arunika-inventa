<?php

/*
|==========================================================================
| MOBILE API v1 — MODUL TAMBAHAN
|==========================================================================
|
| Dimuat dari routes/api.php (prefix /api sudah diberikan oleh Laravel).
| Dokumentasi lengkap: docs/api/MOBILE_API.md
|
*/

use App\Http\Controllers\Api\V1\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Asset\AssetManageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\Maintenance\MaintenanceScheduleController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |----------------------------------------------------------------------
    | PUBLIK
    |----------------------------------------------------------------------
    */

    // Info aplikasi: versi minimal (paksa update), tautan toko, dukungan.
    Route::get('/app-info', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => config('app.name'),
                'api_version' => 'v1',
                'min_app_version' => config('mobile.min_app_version'),
                'latest_app_version' => config('mobile.latest_app_version'),
                'store_url' => [
                    'android' => config('mobile.store_url.android'),
                    'ios' => config('mobile.store_url.ios'),
                ],
                'web_url' => url('/'),
                'support_email' => config('mobile.support_email'),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    })->name('app-info');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')
        ->name('forgot-password');

    Route::middleware(['auth:sanctum', 'api.company', 'api.security'])->group(function () {

        /*
        |------------------------------------------------------------------
        | AKUN — tetap bisa diakses walau langganan berakhir
        |------------------------------------------------------------------
        */

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'changePassword'])
            ->middleware('throttle:10,1')
            ->name('profile.password');

        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('/devices', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

        // Status langganan tetap bisa dilihat admin walau sudah berakhir.
        Route::middleware('administrator')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/subscription', [AdminSubscriptionController::class, 'show'])->name('subscription.show');
            Route::get('/subscription/payments', [AdminSubscriptionController::class, 'payments'])->name('subscription.payments');
        });

        /*
        |------------------------------------------------------------------
        | FITUR — butuh langganan aktif
        |------------------------------------------------------------------
        */

        Route::middleware('subscription.access')->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // Data pilihan untuk form.
            Route::prefix('lookups')->name('lookups.')->group(function () {
                Route::get('/categories', [LookupController::class, 'categories'])->name('categories');
                Route::get('/vendors', [LookupController::class, 'vendors'])->name('vendors');
                Route::get('/users', [LookupController::class, 'users'])->name('users');
                Route::get('/options', [LookupController::class, 'options'])->name('options');
            });

            // Kalender.
            Route::prefix('calendar/events')->name('calendar.')->group(function () {
                Route::get('/', [CalendarController::class, 'index'])->name('index');
                Route::post('/', [CalendarController::class, 'store'])->name('store');
                Route::get('/{id}', [CalendarController::class, 'show'])->whereNumber('id')->name('show');
                Route::put('/{id}', [CalendarController::class, 'update'])->whereNumber('id')->name('update');
                Route::delete('/{id}', [CalendarController::class, 'destroy'])->whereNumber('id')->name('destroy');
            });

            // Kelola aset.
            Route::post('/assets', [AssetManageController::class, 'store'])
                ->middleware('permission:asset.create')
                ->name('assets.store');

            Route::put('/assets/{id}', [AssetManageController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:asset.edit')
                ->name('assets.update');

            Route::delete('/assets/{id}', [AssetManageController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:asset.delete')
                ->name('assets.destroy');

            Route::get('/assets/{id}/assignments', [AssetManageController::class, 'assignments'])
                ->whereNumber('id')
                ->middleware('permission:asset.view')
                ->name('assets.assignments');

            Route::post('/assets/{id}/assign', [AssetManageController::class, 'assign'])
                ->whereNumber('id')
                ->middleware('permission:asset.edit')
                ->name('assets.assign');

            Route::post('/assets/{id}/return', [AssetManageController::class, 'returnAsset'])
                ->whereNumber('id')
                ->middleware('permission:asset.edit')
                ->name('assets.return');

            // Jadwal & riwayat maintenance.
            Route::middleware('permission:maintenance.view')->group(function () {
                Route::get('/maintenances', [MaintenanceScheduleController::class, 'index'])->name('maintenances.index');
                Route::get('/maintenances/{id}', [MaintenanceScheduleController::class, 'show'])->whereNumber('id')->name('maintenances.show');
            });

            // User perusahaan.
            Route::middleware('permission:user.view')->group(function () {
                Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('users.show');
            });

            // Audit trail — khusus Administrator.
            Route::middleware('administrator')->prefix('admin/audit-logs')->name('admin.audit-logs.')->group(function () {
                Route::get('/', [AdminAuditLogController::class, 'index'])->name('index');
                Route::get('/filters', [AdminAuditLogController::class, 'filters'])->name('filters');
                Route::get('/{id}', [AdminAuditLogController::class, 'show'])->whereNumber('id')->name('show');
            });
        });
    });
});
