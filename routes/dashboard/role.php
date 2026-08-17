<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\RoleController;

Route::prefix('cms')
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
        
        Route::get('/roles/permission/{role}', [RoleController::class, 'permission'])
            ->name('roles.permission');

        Route::post('/roles/permission/{role}', [RoleController::class, 'savePermission'])
            ->name('roles.permission.save');
            
        Route::get('/roles/permission/{role}', [RoleController::class, 'permission'])
            ->name('roles.permission');

        Route::post('/roles/permission/{role}', [RoleController::class, 'savePermission'])
            ->name('roles.permission.save');
    });