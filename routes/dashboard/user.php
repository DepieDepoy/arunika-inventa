<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\UserController;

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {

        // User
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::post('/users/store', [UserController::class, 'store'])
            ->name('users.store');

        Route::get('/users/edit/{id}', [UserController::class, 'edit'])
            ->name('users.edit');

        Route::post('/users/update', [UserController::class, 'update'])
            ->name('users.update');

        Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])
            ->name('users.destroy');

        Route::get('/users/data', [UserController::class, 'data'])
            ->name('users.data');

        Route::get('/users/export', [UserController::class, 'export'])
            ->name('users.export');

        // Permission
        Route::get('/users/permission/{role}', [UserController::class, 'permission'])
            ->name('users.permission');

        Route::post('/users/permission/{role}', [UserController::class, 'savePermission'])
            ->name('users.permission.save');

        
        Route::get('/users/import', [UserController::class, 'import'])
            ->name('users.import');

        Route::post('/users/import/preview', [UserController::class, 'previewImport'])
            ->name('users.import.preview');

        Route::post('/users/import/store', [UserController::class, 'importStore'])
            ->name('users.import.store');

        Route::get('/users/import/template', [UserController::class, 'downloadImportTemplate'])
            ->name('users.import.template');

        Route::get('/users/import/history', [UserController::class, 'importHistory'])
            ->name('users.import.history');

        Route::get('/users/import/history/progress', [UserController::class, 'importHistoryProgress'])
            ->name('users.import.history.progress');

        Route::get('/users/import/history/{id}/errors', [UserController::class, 'importHistoryErrors'])
            ->name('users.import.history.errors');
            
        Route::get('/users/import/history/{id}', [UserController::class, 'importHistoryDetail'])
            ->name('users.import.history.detail');

    });