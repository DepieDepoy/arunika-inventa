<?php

use App\Http\Controllers\Dashboard\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LAPORAN
|--------------------------------------------------------------------------
| Mingguan / bulanan / tahunan: tampilan web, cetak (PDF), Excel, PowerPoint.
*/

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('permission:report.view')
            ->name('reports.index');

        Route::get('/reports/print', [ReportController::class, 'print'])
            ->middleware('permission:report.view')
            ->name('reports.print');

        Route::get('/reports/excel', [ReportController::class, 'excel'])
            ->middleware(['permission:report.export', 'throttle:20,1'])
            ->name('reports.excel');

        Route::get('/reports/pptx', [ReportController::class, 'pptx'])
            ->middleware(['permission:report.export', 'throttle:20,1'])
            ->name('reports.pptx');
    });
