<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\SubcategoryController;

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {

        // User
        Route::get('/subcategory', [SubcategoryController::class, 'index'])
            ->name('subcategory.index');

        Route::post('/subcategory/store', [SubcategoryController::class, 'store'])
            ->name('subcategory.store');

        Route::get('/subcategory/edit/{id}', [SubcategoryController::class, 'edit'])
            ->name('subcategory.edit');

        Route::post('/subcategory/update', [SubcategoryController::class, 'update'])
            ->name('subcategory.update');

        Route::delete('/subcategory/delete/{id}', [SubcategoryController::class, 'destroy'])
            ->name('subcategory.destroy');

        Route::get('/subcategory/data', [SubcategoryController::class, 'data'])
            ->name('subcategory.data');

        Route::get('/subcategory/export', [SubcategoryController::class, 'export'])
            ->name('subcategory.export');

    });