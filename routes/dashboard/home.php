<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;

Route::prefix('cms')
    ->middleware('auth')
    ->name('cms.')
    ->group(function () {

    Route::get('/home', [HomeController::class, 'index'])
        ->name('home');

});

