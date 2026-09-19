<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Dashboard\MaintenanceController;
use App\Http\Controllers\Dashboard\MaintenanceRequestController;


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Maintenance Requests
        |--------------------------------------------------------------------------
        |
        | Menampilkan seluruh request maintenance/repair/problem yang masih aktif:
        |
        | pending
        | in_progress
        |
        | Request completed tidak lagi muncul di sini dan masuk ke History.
        |
        */

        Route::get(
            '/maintenance-requests',
            [MaintenanceRequestController::class, 'index']
        )
            ->middleware('permission:maintenance.view')
            ->name('maintenance.requests.index');


        /*
        |--------------------------------------------------------------------------
        | Maintenance Request - Create
        |--------------------------------------------------------------------------
        |
        | Membuat request dari My Assets.
        |
        */

        Route::post(
            '/maintenance-requests',
            [MaintenanceRequestController::class, 'store']
        )
            ->middleware('permission:maintenance.create')
            ->name('maintenance.requests.store');


        /*
        |--------------------------------------------------------------------------
        | Maintenance Request - Update
        |--------------------------------------------------------------------------
        |
        | User dapat mengedit request miliknya sendiri selama status masih
        | pending.
        |
        */

        Route::put(
            '/maintenance-requests/{maintenanceRequest}',
            [MaintenanceRequestController::class, 'update']
        )
            ->middleware('permission:maintenance.edit')
            ->name('maintenance.requests.update');


        /*
        |--------------------------------------------------------------------------
        | Maintenance Request - Detail
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/maintenance-requests/{maintenanceRequest}/detail',
            [MaintenanceRequestController::class, 'show']
        )
            ->middleware('permission:maintenance.view')
            ->name('maintenance.requests.show');


        /*
        |--------------------------------------------------------------------------
        | Take Maintenance Request
        |--------------------------------------------------------------------------
        |
        | Pending -> In Progress
        |
        */

        Route::post(
            '/maintenance-requests/{maintenanceRequest}/take',
            [MaintenanceRequestController::class, 'take']
        )
            ->middleware('permission:maintenance.edit')
            ->name('maintenance.requests.take');


        /*
        |--------------------------------------------------------------------------
        | Work / Progress Page
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/maintenance-requests/{maintenanceRequest}/work',
            [MaintenanceRequestController::class, 'work']
        )
            ->middleware('permission:maintenance.edit')
            ->name('maintenance.requests.work');


        /*
        |--------------------------------------------------------------------------
        | Add Progress
        |--------------------------------------------------------------------------
        |
        | User yang menangani request dapat menambahkan progress berkali-kali.
        |
        */

        Route::post(
            '/maintenance-requests/{maintenanceRequest}/progress',
            [MaintenanceRequestController::class, 'addProgress']
        )
            ->middleware('permission:maintenance.edit')
            ->name('maintenance.requests.progress');


        /*
        |--------------------------------------------------------------------------
        | Complete Maintenance Request
        |--------------------------------------------------------------------------
        |
        | In Progress -> Completed
        |
        | Setelah completed:
        | - hilang dari Maintenance Requests
        | - masuk ke Maintenance History
        |
        */

        Route::post(
            '/maintenance-requests/{maintenanceRequest}/complete',
            [MaintenanceRequestController::class, 'complete']
        )
            ->middleware('permission:maintenance.edit')
            ->name('maintenance.requests.complete');


        /*
        |--------------------------------------------------------------------------
        | All Maintenance
        |--------------------------------------------------------------------------
        |
        | Menampilkan seluruh asset perusahaan yang memiliki jadwal maintenance.
        |
        | Digunakan untuk melihat:
        | - Maintenance overdue
        | - Maintenance hari ini
        | - Maintenance minggu ini
        | - Maintenance berikutnya
        |
        */

        Route::get(
            '/maintenance',
            [MaintenanceController::class, 'index']
        )
            ->middleware('permission:maintenance.view')
            ->name('maintenance.index');


        /*
        |--------------------------------------------------------------------------
        | My Assets
        |--------------------------------------------------------------------------
        |
        | Hanya asset yang responsible_user_id-nya sama dengan user login.
        |
        | Dari halaman ini user dapat membuat:
        | - Maintenance Request
        | - Repair Request
        | - Problem Report
        | - Other Request
        |
        */

        Route::get(
            '/my-assets',
            [MaintenanceController::class, 'myAssets']
        )
            ->middleware('permission:maintenance.create')
            ->name('my-assets.index');


        /*
        |--------------------------------------------------------------------------
        | Maintenance History
        |--------------------------------------------------------------------------
        |
        | Menampilkan maintenance/request yang sudah selesai.
        |
        */

        Route::get(
            '/maintenance/history',
            [MaintenanceController::class, 'history']
        )
            ->middleware('permission:maintenance.history')
            ->name('maintenance.history');

    });
