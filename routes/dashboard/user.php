<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\UserController;

Route::prefix('cms')
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
    });