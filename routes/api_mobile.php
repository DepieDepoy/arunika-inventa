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
use App\Http\Controllers\Api\V1\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\UserManageController;
use App\Http\Controllers\Api\V1\Asset\AssetManageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\IntelligenceController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\Maintenance\MaintenanceScheduleController;
use App\Http\Controllers\Api\V1\Maintenance\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\MasterData\CategoryController;
use App\Http\Controllers\Api\V1\MasterData\VendorController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |----------------------------------------------------------------------
    | PUBLIK
    |----------------------------------------------------------------------
    */

    // Info aplikasi: wajib update, pemeliharaan, tautan legal, fitur aktif.
    Route::get('/app-info', \App\Http\Controllers\Api\V1\AppInfoController::class)->name('app-info');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')
        ->name('forgot-password');

    Route::middleware(['auth:sanctum', 'api.company', 'api.security', 'api.locale'])->group(function () {

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

        // Hapus akun sendiri (wajib Google Play & App Store).
        Route::post('/account/delete', [\App\Http\Controllers\Api\V1\AccountController::class, 'destroy'])
            ->middleware('throttle:5,1')
            ->name('account.delete');

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

            Route::get('/assets/{id}/locations', [AssetManageController::class, 'locationHistory'])
                ->whereNumber('id')
                ->middleware('permission:asset.view|asset.move')
                ->name('assets.locations');

            Route::post('/assets/{id}/location', [AssetManageController::class, 'moveLocation'])
                ->whereNumber('id')
                ->middleware('permission:asset.move')
                ->name('assets.location.move');

            // Jadwal & riwayat maintenance.
            Route::middleware('permission:maintenance.view')->group(function () {
                Route::get('/maintenances', [MaintenanceScheduleController::class, 'index'])->name('maintenances.index');
                Route::get('/maintenances/{id}', [MaintenanceScheduleController::class, 'show'])->whereNumber('id')->name('maintenances.show');
            });

            // Request maintenance oleh pemohon (semua jenis, edit & batal selama menunggu).
            Route::middleware('permission:maintenance.create')->group(function () {
                Route::post('/assets/{assetId}/requests', [MaintenanceRequestController::class, 'storeRequest'])
                    ->whereNumber('assetId')->name('assets.requests.store');
                Route::put('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'update'])
                    ->whereNumber('id')->name('maintenance-requests.update');
                Route::post('/maintenance-requests/{id}/cancel', [MaintenanceRequestController::class, 'cancel'])
                    ->whereNumber('id')->name('maintenance-requests.cancel');
            });

            // User perusahaan.
            Route::middleware('permission:user.view')->group(function () {
                Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                Route::get('/users/roles', [UserManageController::class, 'roles'])->name('users.roles');
                Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('users.show');
            });
            Route::post('/users', [UserManageController::class, 'store'])
                ->middleware('permission:user.create')->name('users.store');
            Route::put('/users/{id}', [UserManageController::class, 'update'])
                ->whereNumber('id')->middleware('permission:user.edit')->name('users.update');
            Route::delete('/users/{id}', [UserManageController::class, 'destroy'])
                ->whereNumber('id')->middleware('permission:user.delete')->name('users.destroy');

            // Role & permission.
            Route::prefix('roles')->name('roles.')->group(function () {
                Route::get('/', [AdminRoleController::class, 'index'])->middleware('permission:role.view')->name('index');
                Route::get('/permissions', [AdminRoleController::class, 'permissions'])->middleware('permission:role.view')->name('permissions');
                Route::get('/{id}', [AdminRoleController::class, 'show'])->whereNumber('id')->middleware('permission:role.view')->name('show');
                Route::post('/', [AdminRoleController::class, 'store'])->middleware('permission:role.create')->name('store');
                Route::put('/{id}', [AdminRoleController::class, 'update'])->whereNumber('id')->middleware('permission:role.edit')->name('update');
                Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->whereNumber('id')->middleware('permission:role.delete')->name('destroy');
                Route::put('/{id}/permissions', [AdminRoleController::class, 'syncPermissions'])->whereNumber('id')->middleware('permission:role.permission')->name('permissions.sync');
            });

            // Kategori & subkategori.
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->middleware('permission:category.view')->name('index');
                Route::get('/{id}', [CategoryController::class, 'show'])->whereNumber('id')->middleware('permission:category.view')->name('show');
                Route::post('/', [CategoryController::class, 'store'])->middleware('permission:category.create')->name('store');
                Route::put('/{id}', [CategoryController::class, 'update'])->whereNumber('id')->middleware('permission:category.edit')->name('update');
                Route::delete('/{id}', [CategoryController::class, 'destroy'])->whereNumber('id')->middleware('permission:category.delete')->name('destroy');
                Route::post('/{id}/sub-categories', [CategoryController::class, 'storeSub'])->whereNumber('id')->middleware('permission:subcategory.create')->name('sub.store');
            });
            Route::put('/sub-categories/{id}', [CategoryController::class, 'updateSub'])
                ->whereNumber('id')->middleware('permission:subcategory.edit')->name('sub-categories.update');
            Route::delete('/sub-categories/{id}', [CategoryController::class, 'destroySub'])
                ->whereNumber('id')->middleware('permission:subcategory.delete')->name('sub-categories.destroy');

            // Vendor.
            Route::prefix('vendors')->name('vendors.')->group(function () {
                Route::get('/', [VendorController::class, 'index'])->middleware('permission:vendor.view')->name('index');
                Route::get('/{id}', [VendorController::class, 'show'])->whereNumber('id')->middleware('permission:vendor.view')->name('show');
                Route::post('/', [VendorController::class, 'store'])->middleware('permission:vendor.create')->name('store');
                Route::put('/{id}', [VendorController::class, 'update'])->whereNumber('id')->middleware('permission:vendor.edit')->name('update');
                Route::delete('/{id}', [VendorController::class, 'destroy'])->whereNumber('id')->middleware('permission:vendor.delete')->name('destroy');
            });

            // Laporan mingguan / bulanan / tahunan.
            Route::get('/reports', [\App\Http\Controllers\Api\V1\ReportController::class, 'show'])
                ->middleware('permission:report.view')->name('reports.show');
            Route::get('/reports/download', [\App\Http\Controllers\Api\V1\ReportController::class, 'download'])
                ->middleware(['permission:report.export', 'throttle:20,1'])->name('reports.download');

            // Vasetra AI.
            Route::prefix('ai')->name('ai.')->middleware('throttle:20,1')->group(function () {
                Route::post('/ask', [IntelligenceController::class, 'ask'])->name('ask');
                Route::post('/confirm', [IntelligenceController::class, 'confirm'])->name('confirm');
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
