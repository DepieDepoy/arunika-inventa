<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\PermissionController;

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {

        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index');

        Route::post('/roles/store', [RoleController::class, 'store'])
            ->name('roles.store');

        Route::get('/roles/edit/{id}', [RoleController::class, 'edit'])
            ->name('roles.edit');

        Route::post('/roles/update', [RoleController::class, 'update'])
            ->name('roles.update');
        
        Route::delete('/roles/delete/{id}', [RoleController::class, 'destroy'])
            ->name('roles.destroy');
        
        Route::get('/roles/data', [RoleController::class, 'data'])
            ->name('roles.data');

        Route::get('/roles/export', [RoleController::class, 'export'])
            ->name('roles.export');
        
        // =====================================================
        // ROLE PERMISSION
        // =====================================================
        Route::get('/roles/permission/{role}', [PermissionController::class, 'index'])
            ->name('roles.permission');

        Route::post('/roles/permission/{role}', [PermissionController::class, 'savePermission'])
            ->name('roles.permission.save');

    });