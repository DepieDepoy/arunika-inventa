<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\VendorController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // VENDOR
        // =====================================================

        // View Vendor
        Route::get('/vendor', [VendorController::class, 'index'])
            ->middleware('permission:vendor.view')
            ->name('vendor.index');


        // Create Vendor
        Route::post('/vendor/store', [VendorController::class, 'store'])
            ->middleware([
                'permission:vendor.create',
                'subscription.active'
            ])
            ->name('vendor.store');


        // Edit Vendor
        Route::get('/vendor/edit/{id}', [VendorController::class, 'edit'])
            ->middleware([
                'permission:vendor.edit',
                'subscription.active'
            ])
            ->name('vendor.edit');


        // Update Vendor
        Route::post('/vendor/update', [VendorController::class, 'update'])
            ->middleware([
                'permission:vendor.edit',
                'subscription.active'
            ])
            ->name('vendor.update');


        // Delete Vendor
        Route::delete('/vendor/delete/{id}', [VendorController::class, 'destroy'])
            ->middleware([
                'permission:vendor.delete',
                'subscription.active'
            ])
            ->name('vendor.destroy');


        // DataTable Vendor
        Route::get('/vendor/data', [VendorController::class, 'data'])
            ->middleware('permission:vendor.view')
            ->name('vendor.data');


        // Export Vendor
        Route::get('/vendor/export', [VendorController::class, 'export'])
            ->middleware([
                'permission:vendor.export',
                'subscription.active'
            ])
            ->name('vendor.export');

    });
