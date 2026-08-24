<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\VendorController;

Route::prefix('cms')
    ->middleware('auth')
    ->group(function () {

        // vendor
        Route::get('/vendor', [VendorController::class, 'index'])
            ->name('vendor.index');

        Route::post('/vendor/store', [VendorController::class, 'store'])
            ->name('vendor.store');

        Route::get('/vendor/edit/{id}', [VendorController::class, 'edit'])
            ->name('vendor.edit');

        Route::post('/vendor/update', [VendorController::class, 'update'])
            ->name('vendor.update');

        Route::delete('/vendor/delete/{id}', [VendorController::class, 'destroy'])
            ->name('vendor.destroy');

        Route::get('/vendor/data', [VendorController::class, 'data'])
            ->name('vendor.data');

        Route::get('/vendor/export', [VendorController::class, 'export'])
            ->name('vendor.export');
        
    });