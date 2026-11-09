<?php

use App\Http\Controllers\Dashboard\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // MASTER LOKASI (per cabang)
        // =====================================================

        Route::get('/locations', [LocationController::class, 'index'])
            ->middleware('permission:location.view')
            ->name('locations.index');

        // Dropdown lokasi (form aset, pindah lokasi, mutasi, audit).
        Route::get('/locations/options', [LocationController::class, 'options'])
            ->name('locations.options');

        Route::post('/locations', [LocationController::class, 'store'])
            ->middleware(['permission:location.create|asset.create|asset.move', 'subscription.active'])
            ->name('locations.store');

        Route::post('/locations/{location}', [LocationController::class, 'update'])
            ->whereNumber('location')
            ->middleware(['permission:location.edit', 'subscription.active'])
            ->name('locations.update');

        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])
            ->whereNumber('location')
            ->middleware(['permission:location.delete', 'subscription.active'])
            ->name('locations.destroy');
    });
