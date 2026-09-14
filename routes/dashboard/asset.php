<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\AssetController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // ASSET
        // =====================================================

        // View Asset
        Route::get('/assets', [AssetController::class, 'index'])
            ->middleware('permission:asset.view')
            ->name('assets.index');


        // Create Asset - Form
        Route::get('/assets/create', [AssetController::class, 'create'])
            ->middleware([
                'permission:asset.create',
                'subscription.active'
            ])
            ->name('assets.create');


        // Store Asset
        Route::post('/assets/store', [AssetController::class, 'store'])
            ->middleware([
                'permission:asset.create',
                'subscription.active'
            ])
            ->name('assets.store');


        // Edit Asset - Form
        Route::get('/assets/edit/{id}', [AssetController::class, 'edit'])
            ->middleware([
                'permission:asset.edit',
                'subscription.active'
            ])
            ->name('assets.edit');


        // Update Asset
        Route::post('/assets/update', [AssetController::class, 'update'])
            ->middleware([
                'permission:asset.edit',
                'subscription.active'
            ])
            ->name('assets.update');


        // View Asset Detail
        Route::get('/assets/view/{id}', [AssetController::class, 'show'])
            ->middleware([
                'permission:asset.view',
                'subscription.active'
            ])
            ->name('assets.show');


        // Delete Asset
        Route::delete('/assets/delete/{id}', [AssetController::class, 'destroy'])
            ->middleware([
                'permission:asset.delete',
                'subscription.active'
            ])
            ->name('assets.destroy');


        // DataTable Asset
        Route::get('/assets/data', [AssetController::class, 'data'])
            ->middleware('permission:asset.view')
            ->name('assets.data');


        // =====================================================
        // QR CODE
        // =====================================================

        // Generate QR Asset
        Route::get('/assets/qr/{id}', [AssetController::class, 'qr'])
            ->middleware([
                'permission:asset.print_qr',
                'subscription.active'
            ])
            ->name('assets.qr');


        // Print QR Multiple Asset
        Route::post('/assets/print-qr', [AssetController::class, 'printQr'])
            ->middleware([
                'permission:asset.print_qr',
                'subscription.active'
            ])
            ->name('assets.print-qr');


        // =====================================================
        // EXPORT
        // =====================================================

        // Export Asset
        Route::get('/assets/export', [AssetController::class, 'export'])
            ->middleware([
                'permission:asset.export',
                'subscription.active'
            ])
            ->name('assets.export');


        // =====================================================
        // IMPORT
        // =====================================================

        // Import Asset - Form
        Route::get('/assets/import', [AssetController::class, 'import'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import');


        // Import Preview
        Route::post('/assets/import/preview', [AssetController::class, 'previewImport'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import.preview');


        // Import Store
        Route::post('/assets/import/store', [AssetController::class, 'importStore'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import.store');


        // Download Import Template
        Route::get('/assets/import/template', [AssetController::class, 'downloadImportTemplate'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import.template');


        // Import History
        Route::get('/assets/import/history', [AssetController::class, 'importHistory'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import.history');


        // Import History Progress
        Route::get('/assets/import/history/progress', [AssetController::class, 'importHistoryProgress'])
            ->middleware([
                'permission:asset.import',
                'subscription.active'
            ])
            ->name('assets.import.history.progress');

    });
