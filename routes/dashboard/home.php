<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\IntelligenceController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->name('dashboard.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Intelligence Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/intelligence', [
            HomeController::class,
            'intelligence'
        ])->name('intelligence');


        /*
        |--------------------------------------------------------------------------
        | Ask Vasetra AI
        |--------------------------------------------------------------------------
        */

        Route::post('/intelligence/ask', [
            IntelligenceController::class,
            'ask'
        ])->name('intelligence.ask');


        /*
        |--------------------------------------------------------------------------
        | Confirm Vasetra AI Action
        |--------------------------------------------------------------------------
        */

        Route::post('/intelligence/confirm', [
            IntelligenceController::class,
            'confirm'
        ])->name('intelligence.confirm');


        /*
        |--------------------------------------------------------------------------
        | Operational Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/home', [
            HomeController::class,
            'index'
        ])->name('home');

    });