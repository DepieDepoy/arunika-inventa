<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\CategoryController;

Route::prefix('cms')
    ->middleware('auth')
    ->group(function () {

        // Category
        Route::get('/category', [CategoryController::class, 'index'])
            ->name('category.index');

        Route::post('/category/store', [CategoryController::class, 'store'])
            ->name('category.store');

        Route::get('/category/edit/{id}', [CategoryController::class, 'edit'])
            ->name('category.edit');

        Route::post('/category/update', [CategoryController::class, 'update'])
            ->name('category.update');

        Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy'])
            ->name('category.destroy');

        Route::get('/category/data', [CategoryController::class, 'data'])
            ->name('category.data');

        Route::get('/category/export', [CategoryController::class, 'export'])
            ->name('category.export');
        
         // =========================================================
        // Sub Category
        // =========================================================

        // Get Sub Category berdasarkan Category
        Route::get(
            '/category/{categoryId}/sub-categories',
            [CategoryController::class, 'subCategories']
        )->name('category.subcategories');

        // Store Sub Category
        Route::post(
            '/category/sub-category/store',
            [CategoryController::class, 'storeSubCategory']
        )->name('category.subcategory.store');

        // Edit Sub Category
        Route::get(
            '/category/sub-category/edit/{id}',
            [CategoryController::class, 'editSubCategory']
        )->name('category.subcategory.edit');

        // Update Sub Category
        Route::post(
            '/category/sub-category/update',
            [CategoryController::class, 'updateSubCategory']
        )->name('category.subcategory.update');

        // Delete Sub Category
        Route::delete(
            '/category/sub-category/delete/{id}',
            [CategoryController::class, 'destroySubCategory']
        )->name('category.subcategory.destroy');
        
        Route::get(
            '/category/{categoryId}/subcategories',
            [CategoryController::class, 'subCategories']
        )->name('category.subcategories');
        
        Route::get(
            '/category/list',
            [CategoryController::class, 'list']
        )->name('category.list');
    });