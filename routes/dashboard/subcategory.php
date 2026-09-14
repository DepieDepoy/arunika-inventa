<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\SubcategoryController;

Route::prefix('dashboard')
    ->middleware('auth','subscription.access')
    ->group(function () {

        // User
        Route::get('/subcategory', [SubcategoryController::class, 'index'])
            ->middleware('permission:subcategory.view')
            ->name('subcategory.index');

        Route::post('/subcategory/store', [SubcategoryController::class, 'store'])
            ->middleware('permission:subcategory.store','subscription.active')
            ->name('subcategory.store');

        Route::get('/subcategory/edit/{id}', [SubcategoryController::class, 'edit'])
            ->middleware('permission:subcategory.edit','subscription.active')
            ->name('subcategory.edit');

        Route::post('/subcategory/update', [SubcategoryController::class, 'update'])
            ->middleware('permission:subcategory.update','subscription.active')
            ->name('subcategory.update');

        Route::delete('/subcategory/delete/{id}', [SubcategoryController::class, 'destroy'])
            ->middleware('permission:subcategory.destroy','subscription.active')
            ->name('subcategory.destroy');

        Route::get('/subcategory/data', [SubcategoryController::class, 'data'])
            ->middleware('permission:subcategory.view')
            ->name('subcategory.data');

        Route::get('/subcategory/export', [SubcategoryController::class, 'export'])
            ->middleware('permission:subcategory.export','subscription.active')
            ->name('subcategory.export');

    });