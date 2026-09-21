<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Asset\AssetController;
use App\Http\Controllers\Api\V1\Maintenance\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | API TEST
    |--------------------------------------------------------------------------
    */

    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'VASETRA API is working.',
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )
        ->middleware('throttle:api-login')
        ->name('api.v1.login');


    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED API
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        'api.company',
        'api.security',
    ])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | CURRENT USER
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/me',
            [AuthController::class, 'me']
        )->name('api.v1.me');


        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        )->name('api.v1.logout');


        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION ACCESS
        |--------------------------------------------------------------------------
        */

        Route::middleware('subscription.access')->group(function () {

            /*
            |--------------------------------------------------------------------------
            | ASSET VIEW
            |--------------------------------------------------------------------------
            |
            | Mobile Asset:
            |
            | ALL ASSETS
            | GET /api/v1/assets
            | GET /api/v1/assets/{id}
            | GET /api/v1/assets/{id}/maintenances
            |
            | MY ASSETS
            | GET /api/v1/my-assets
            | GET /api/v1/my-assets/{id}
            | GET /api/v1/my-assets/{id}/maintenances
            |
            | SCAN QR
            | GET /api/v1/assets/qr/{qrToken}
            |
            */

            Route::middleware(
                'permission:asset.view'
            )->group(function () {

                /*
                |--------------------------------------------------------------------------
                | SCAN QR ASSET
                |--------------------------------------------------------------------------
                |
                | User harus:
                | - sudah login
                | - memiliki permission asset.view
                | - berada dalam company yang sama dengan asset
                |
                */

                Route::get(
                    '/assets/qr/{qrToken}',
                    [AssetController::class, 'scanQr']
                )->name(
                    'api.v1.assets.scan-qr'
                );


                /*
                |--------------------------------------------------------------------------
                | MY ASSETS
                |--------------------------------------------------------------------------
                |
                | Hanya asset yang:
                | responsible_user_id = user yang sedang login
                |
                */

                Route::get(
                    '/my-assets',
                    [AssetController::class, 'myAssets']
                )->name(
                    'api.v1.my-assets.index'
                );


                /*
                |--------------------------------------------------------------------------
                | MY ASSET DETAIL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/my-assets/{id}',
                    [AssetController::class, 'myAssetShow']
                )->name(
                    'api.v1.my-assets.show'
                );


                /*
                |--------------------------------------------------------------------------
                | MY ASSET MAINTENANCE HISTORY
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/my-assets/{id}/maintenances',
                    [AssetController::class, 'myAssetMaintenances']
                )->name(
                    'api.v1.my-assets.maintenances'
                );


                /*
                |--------------------------------------------------------------------------
                | ALL ASSET MAINTENANCE HISTORY
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/assets/{id}/maintenances',
                    [AssetController::class, 'maintenances']
                )->name(
                    'api.v1.assets.maintenances'
                );


                /*
                |--------------------------------------------------------------------------
                | ALL ASSETS
                |--------------------------------------------------------------------------
                |
                | Menampilkan seluruh asset dalam company user.
                |
                */

                Route::get(
                    '/assets',
                    [AssetController::class, 'index']
                )->name(
                    'api.v1.assets.index'
                );


                /*
                |--------------------------------------------------------------------------
                | ALL ASSET DETAIL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/assets/{id}',
                    [AssetController::class, 'show']
                )->name(
                    'api.v1.assets.show'
                );
            });


            /*
            |--------------------------------------------------------------------------
            | USER - MAINTENANCE REQUEST
            |--------------------------------------------------------------------------
            |
            | User hanya dapat membuat request untuk asset yang menjadi
            | responsible_user_id miliknya.
            |
            | Permission:
            | maintenance.create
            |
            */

            Route::middleware(
                'permission:maintenance.create'
            )->group(function () {

                /*
                |--------------------------------------------------------------------------
                | CREATE MAINTENANCE REQUEST
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/assets/{assetId}/maintenance-request',
                    [
                        MaintenanceRequestController::class,
                        'storeMaintenanceRequest',
                    ]
                )->name(
                    'api.v1.assets.maintenance-request'
                );


                /*
                |--------------------------------------------------------------------------
                | CREATE REPAIR REQUEST
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/assets/{assetId}/repair-request',
                    [
                        MaintenanceRequestController::class,
                        'storeRepairRequest',
                    ]
                )->name(
                    'api.v1.assets.repair-request'
                );
            });


            /*
            |--------------------------------------------------------------------------
            | USER - MY REQUESTS
            |--------------------------------------------------------------------------
            |
            | User hanya melihat request yang dibuat oleh dirinya sendiri.
            |
            | Permission:
            | asset.view
            |
            */

            Route::middleware(
                'permission:asset.view'
            )->group(function () {

                /*
                |--------------------------------------------------------------------------
                | LIST MY REQUESTS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/maintenance-requests',
                    [
                        MaintenanceRequestController::class,
                        'index',
                    ]
                )->name(
                    'api.v1.maintenance-requests.index'
                );


                /*
                |--------------------------------------------------------------------------
                | DETAIL MY REQUEST
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/maintenance-requests/{id}',
                    [
                        MaintenanceRequestController::class,
                        'show',
                    ]
                )->name(
                    'api.v1.maintenance-requests.show'
                );
            });


            /*
            |--------------------------------------------------------------------------
            | TEAM - MAINTENANCE REQUEST
            |--------------------------------------------------------------------------
            |
            | Team dapat melihat seluruh request dalam company.
            |
            | Permission:
            | maintenance.view
            |
            */

            Route::middleware(
                'permission:maintenance.view'
            )->prefix('maintenance/team')->group(
                function () {

                    /*
                    |--------------------------------------------------------------------------
                    | TEAM REQUEST LIST
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/requests',
                        [
                            MaintenanceRequestController::class,
                            'teamIndex',
                        ]
                    )->name(
                        'api.v1.maintenance.team.requests.index'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | TEAM REQUEST DETAIL
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/requests/{id}',
                        [
                            MaintenanceRequestController::class,
                            'teamShow',
                        ]
                    )->name(
                        'api.v1.maintenance.team.requests.show'
                    );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | TEAM - TAKE / PROGRESS / COMPLETE
            |--------------------------------------------------------------------------
            |
            | Permission:
            | maintenance.edit
            |
            */

            Route::middleware(
                'permission:maintenance.edit'
            )->prefix('maintenance/team')->group(
                function () {

                    /*
                    |--------------------------------------------------------------------------
                    | TAKE REQUEST
                    |--------------------------------------------------------------------------
                    |
                    | pending -> in_progress
                    |
                    */

                    Route::post(
                        '/requests/{id}/take',
                        [
                            MaintenanceRequestController::class,
                            'take',
                        ]
                    )->name(
                        'api.v1.maintenance.team.requests.take'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | ADD PROGRESS
                    |--------------------------------------------------------------------------
                    |
                    | Status harus in_progress.
                    | photos[] maksimal 10.
                    |
                    */

                    Route::post(
                        '/requests/{id}/progress',
                        [
                            MaintenanceRequestController::class,
                            'progress',
                        ]
                    )->name(
                        'api.v1.maintenance.team.requests.progress'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | COMPLETE REQUEST
                    |--------------------------------------------------------------------------
                    |
                    | in_progress -> completed
                    | after_photos[] minimal 1, maksimal 10.
                    |
                    */

                    Route::post(
                        '/requests/{id}/complete',
                        [
                            MaintenanceRequestController::class,
                            'complete',
                        ]
                    )->name(
                        'api.v1.maintenance.team.requests.complete'
                    );
                }
            );
        });
    });
});