<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\AssetController;


Route::prefix('cms')
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

        Route::get('/assets/export', [AssetController::class, 'export'])
            ->name('assets.export');

    });