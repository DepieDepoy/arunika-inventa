<?php

use App\Http\Controllers\Dashboard\AssetAuditController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {

        // =====================================================
        // AUDIT FISIK ASET
        // =====================================================

        Route::get('/asset-audits', [AssetAuditController::class, 'index'])
            ->middleware('permission:asset_audit.view')
            ->name('asset-audits.index');

        Route::post('/asset-audits', [AssetAuditController::class, 'store'])
            ->middleware(['permission:asset_audit.create', 'subscription.active'])
            ->name('asset-audits.store');

        Route::get('/asset-audits/{audit}', [AssetAuditController::class, 'show'])
            ->whereNumber('audit')
            ->middleware('permission:asset_audit.view|asset_audit.scan')
            ->name('asset-audits.show');

        Route::post('/asset-audits/{audit}/scan', [AssetAuditController::class, 'scan'])
            ->whereNumber('audit')
            ->middleware(['permission:asset_audit.scan', 'subscription.active', 'throttle:240,1'])
            ->name('asset-audits.scan');

        Route::post('/asset-audits/{audit}/items/{item}', [AssetAuditController::class, 'mark'])
            ->whereNumber(['audit', 'item'])
            ->middleware(['permission:asset_audit.scan', 'subscription.active'])
            ->name('asset-audits.mark');

        Route::get('/asset-audits/{audit}/summary', [AssetAuditController::class, 'summary'])
            ->whereNumber('audit')
            ->middleware('permission:asset_audit.view|asset_audit.scan')
            ->name('asset-audits.summary');

        // Tandai semua aset belum dicek di satu lokasi = ditemukan.
        Route::post('/asset-audits/{audit}/mark-location', [AssetAuditController::class, 'markLocation'])
            ->whereNumber('audit')
            ->middleware(['permission:asset_audit.scan', 'subscription.active'])
            ->name('asset-audits.mark-location');

        Route::post('/asset-audits/{audit}/complete', [AssetAuditController::class, 'complete'])
            ->whereNumber('audit')
            ->middleware(['permission:asset_audit.create', 'subscription.active'])
            ->name('asset-audits.complete');

        Route::post('/asset-audits/{audit}/cancel', [AssetAuditController::class, 'cancel'])
            ->whereNumber('audit')
            ->middleware(['permission:asset_audit.create', 'subscription.active'])
            ->name('asset-audits.cancel');

        Route::get('/asset-audits/{audit}/export', [AssetAuditController::class, 'export'])
            ->whereNumber('audit')
            ->middleware('permission:asset_audit.view')
            ->name('asset-audits.export');
    });
