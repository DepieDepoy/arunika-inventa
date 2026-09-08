<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\AssetController;


Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {

        // Asset
        Route::get('/assets', [AssetController::class, 'index'])
            ->name('assets.index');

        Route::get('/assets/create', [AssetController::class, 'create'])
            ->name('assets.create');

        Route::post('/assets/store', [AssetController::class, 'store'])
            ->name('assets.store');

        Route::get('/assets/edit/{id}', [AssetController::class, 'edit'])
            ->name('assets.edit');

        Route::post('/assets/update', [AssetController::class, 'update'])
            ->name('assets.update');

        Route::get('/assets/view/{id}', [AssetController::class, 'show'])
            ->name('assets.show');

        Route::delete('/assets/delete/{id}', [AssetController::class, 'destroy'])
            ->name('assets.destroy');

        Route::get('/assets/data', [AssetController::class, 'data'])
            ->name('assets.data');

        Route::get('/assets/qr/{id}', [AssetController::class, 'qr'])
            ->name('assets.qr');
            
        Route::post('/assets/print-qr', [AssetController::class, 'printQr'])
            ->name('assets.print-qr');

        Route::get('/assets/export', [AssetController::class, 'export'])
            ->name('assets.export');

        Route::get('/assets/import', [AssetController::class, 'import'])
            ->name('assets.import');

        Route::post('/assets/import/preview', [AssetController::class, 'previewImport'])
            ->name('assets.import.preview');

        Route::post('/assets/import/store', [AssetController::class, 'importStore'])
            ->name('assets.import.store');

        Route::get('/assets/import/template', [AssetController::class, 'downloadImportTemplate']
        )->name('assets.import.template');

        Route::get('/assets/import/history',[AssetController::class, 'importHistory'])
            ->name('assets.import.history');

        Route::get('/assets/import/history/progress',[AssetController::class, 'importHistoryProgress'])
            ->name('assets.import.history.progress');

    });