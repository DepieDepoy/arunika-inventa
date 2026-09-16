<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;

Route::prefix('dashboard')
    ->middleware(['auth', 'subscription.access'])
    ->name('dashboard.')
    ->group(function () {

        Route::get('/home', [HomeController::class, 'index'])
            ->name('home');

    });