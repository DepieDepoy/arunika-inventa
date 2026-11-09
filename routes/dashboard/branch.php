<?php

use App\Http\Controllers\Dashboard\BranchController;
use App\Http\Controllers\Dashboard\BranchTransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // CABANG
        // =====================================================

        Route::get('/branches', [BranchController::class, 'index'])
            ->middleware('permission:branch.view')
            ->name('branches.index');

        Route::post('/branches', [BranchController::class, 'store'])
            ->middleware(['permission:branch.create', 'subscription.active'])
            ->name('branches.store');

        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->whereNumber('branch')
            ->middleware('permission:branch.edit')
            ->name('branches.edit');

        Route::post('/branches/{branch}', [BranchController::class, 'update'])->whereNumber('branch')
            ->middleware(['permission:branch.edit', 'subscription.active'])
            ->name('branches.update');

        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->whereNumber('branch')
            ->middleware(['permission:branch.delete', 'subscription.active'])
            ->name('branches.destroy');

        // =====================================================
        // MUTASI ASET ANTAR-CABANG
        // =====================================================

        Route::get('/branch-transfers', [BranchTransferController::class, 'index'])
            ->middleware('permission:branch.transfer|branch.receive')
            ->name('branch-transfers.index');

        Route::get('/branch-transfers/create', [BranchTransferController::class, 'create'])
            ->middleware(['permission:branch.transfer', 'subscription.active'])
            ->name('branch-transfers.create');

        Route::get('/branch-transfers/assets', [BranchTransferController::class, 'assets'])
            ->middleware('permission:branch.transfer')
            ->name('branch-transfers.assets');

        Route::post('/branch-transfers', [BranchTransferController::class, 'store'])
            ->middleware(['permission:branch.transfer', 'subscription.active'])
            ->name('branch-transfers.store');

        Route::get('/branch-transfers/{transfer}', [BranchTransferController::class, 'show'])->whereNumber('transfer')
            ->middleware('permission:branch.transfer|branch.receive')
            ->name('branch-transfers.show');

        Route::post('/branch-transfers/{transfer}/receive', [BranchTransferController::class, 'receive'])->whereNumber('transfer')
            ->middleware(['permission:branch.receive', 'subscription.active'])
            ->name('branch-transfers.receive');

        Route::post('/branch-transfers/{transfer}/cancel', [BranchTransferController::class, 'cancel'])->whereNumber('transfer')
            ->middleware(['permission:branch.transfer', 'subscription.active'])
            ->name('branch-transfers.cancel');
    });
