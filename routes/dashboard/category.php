<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\CategoryController;

Route::prefix('dashboard')
    ->middleware('auth','subscription.access')
    ->group(function () {

        // =========================================================
        // CATEGORY
        // =========================================================

        // View Category
        Route::get('/category', [CategoryController::class, 'index'])
            ->middleware('permission:category.view')
            ->name('category.index');

        // DataTable Category
        Route::get('/category/data', [CategoryController::class, 'data'])
            ->middleware('permission:category.view')
            ->name('category.data');

        // Create Category
        Route::post('/category/store', [CategoryController::class, 'store'])
            ->middleware('permission:category.create','subscription.active')
            ->name('category.store');

        // Edit Category
        Route::get('/category/edit/{id}', [CategoryController::class, 'edit'])
            ->middleware('permission:category.edit','subscription.active')
            ->name('category.edit');

        // Update Category
        Route::post('/category/update', [CategoryController::class, 'update'])
            ->middleware('permission:category.edit','subscription.active')
            ->name('category.update');

        // Delete Category
        Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy'])
            ->middleware('permission:category.delete','subscription.active')
            ->name('category.destroy');


        // =========================================================
        // SUB CATEGORY
        // =========================================================

        // Get Sub Category berdasarkan Category
        Route::get('/category/{categoryId}/sub-categories',[CategoryController::class, 'subCategories'])
            ->middleware('permission:subcategory.view')
            ->name('category.subcategories');


        // List Category
        Route::get('/category/list',[CategoryController::class, 'list'])
            ->middleware('permission:category.view')
            ->name('category.list');


        // Store Sub Category
        Route::post('/category/sub-category/store',[CategoryController::class, 'storeSubCategory'])
            ->middleware('permission:subcategory.create','subscription.active')
            ->name('category.subcategory.store');


        // Edit Sub Category
        Route::get('/category/sub-category/edit/{id}',[CategoryController::class, 'editSubCategory'])
            ->middleware('permission:subcategory.edit','subscription.active')
            ->name('category.subcategory.edit');


        // Update Sub Category
        Route::post('/category/sub-category/update',[CategoryController::class, 'updateSubCategory'])
            ->middleware('permission:subcategory.edit','subscription.active')
            ->name('category.subcategory.update');


        // Delete Sub Category
        Route::delete('/category/sub-category/delete/{id}',[CategoryController::class, 'destroySubCategory'])
            ->middleware('permission:subcategory.delete','subscription.active')
            ->name('category.subcategory.destroy');

        Route::get('/category/export', [CategoryController::class, 'export'])
            ->middleware('permission:category.export','subscription.active')
            ->name('category.export');

    });