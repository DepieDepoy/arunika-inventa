<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\PermissionController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // ROLE
        // =====================================================

        // View Role
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:role.view')
            ->name('roles.index');


        // Create Role
        Route::post('/roles/store', [RoleController::class, 'store'])
            ->middleware([
                'permission:role.create',
                'subscription.active'
            ])
            ->name('roles.store');


        // Edit Role
        Route::get('/roles/edit/{id}', [RoleController::class, 'edit'])
            ->middleware([
                'permission:role.edit',
                'subscription.active'
            ])
            ->name('roles.edit');


        // Update Role
        Route::post('/roles/update', [RoleController::class, 'update'])
            ->middleware([
                'permission:role.edit',
                'subscription.active'
            ])
            ->name('roles.update');


        // Delete Role
        Route::delete('/roles/delete/{id}', [RoleController::class, 'destroy'])
            ->middleware([
                'permission:role.delete',
                'subscription.active'
            ])
            ->name('roles.destroy');


        // DataTable Role
        Route::get('/roles/data', [RoleController::class, 'data'])
            ->middleware('permission:role.view')
            ->name('roles.data');


        // =====================================================
        // ROLE PERMISSION
        // =====================================================

        // Manage Role Permission - Page
        Route::get('/roles/permission/{role}', [PermissionController::class, 'index'])
            ->middleware([
                'permission:role.permission',
                'subscription.active'
            ])
            ->name('roles.permission');


        // Save Role Permission
        Route::post('/roles/permission/{role}', [PermissionController::class, 'savePermission'])
            ->middleware([
                'permission:role.permission',
                'subscription.active'
            ])
            ->name('roles.permission.save');


        // =====================================================
        // EXPORT ROLE
        // =====================================================
        // Belum ada permission role.export di PermissionSeeder.
        // Untuk sementara tetap menggunakan role.view.

        Route::get('/roles/export', [RoleController::class, 'export'])
            ->middleware([
                'permission:role.view',
                'subscription.active'
            ])
            ->name('roles.export');

    });
