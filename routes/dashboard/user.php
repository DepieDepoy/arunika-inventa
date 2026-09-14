<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\UserController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // USER
        // =====================================================

        // View User
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:user.view')
            ->name('users.index');


        // Create User
        Route::post('/users/store', [UserController::class, 'store'])
            ->middleware([
                'permission:user.create',
                'subscription.active'
            ])
            ->name('users.store');


        // Edit User
        Route::get('/users/edit/{id}', [UserController::class, 'edit'])
            ->middleware([
                'permission:user.edit',
                'subscription.active'
            ])
            ->name('users.edit');


        // Update User
        Route::post('/users/update', [UserController::class, 'update'])
            ->middleware([
                'permission:user.edit',
                'subscription.active'
            ])
            ->name('users.update');


        // Delete User
        Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])
            ->middleware([
                'permission:user.delete',
                'subscription.active'
            ])
            ->name('users.destroy');


        // DataTable User
        Route::get('/users/data', [UserController::class, 'data'])
            ->middleware('permission:user.view')
            ->name('users.data');


        // Export User
        Route::get('/users/export', [UserController::class, 'export'])
            ->middleware([
                'permission:user.export',
                'subscription.active'
            ])
            ->name('users.export');


        // =====================================================
        // USER IMPORT
        // =====================================================

        // Import User - Form
        Route::get('/users/import', [UserController::class, 'import'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import');


        // Import Preview
        Route::post('/users/import/preview', [UserController::class, 'previewImport'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.preview');


        // Import Store
        Route::post('/users/import/store', [UserController::class, 'importStore'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.store');


        // Download Import Template
        Route::get('/users/import/template', [UserController::class, 'downloadImportTemplate'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.template');


        // Import History
        Route::get('/users/import/history', [UserController::class, 'importHistory'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.history');


        // Import History Progress
        Route::get('/users/import/history/progress', [UserController::class, 'importHistoryProgress'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.history.progress');


        // Import History Errors
        Route::get('/users/import/history/{id}/errors', [UserController::class, 'importHistoryErrors'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.history.errors');


        // Import History Detail
        Route::get('/users/import/history/{id}', [UserController::class, 'importHistoryDetail'])
            ->middleware([
                'permission:user.import',
                'subscription.active'
            ])
            ->name('users.import.history.detail');

    });
