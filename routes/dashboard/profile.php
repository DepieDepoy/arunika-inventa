<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\ProfileController;


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')
    ->middleware([
        'auth',
        'subscription.access'
    ])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [
            ProfileController::class,
            'index'
        ])
        ->name('profile.index');


        /*
        |--------------------------------------------------------------------------
        | Update Profile
        |--------------------------------------------------------------------------
        */

        Route::post('/profile/update', [
            ProfileController::class,
            'update'
        ])
        ->name('profile.update');


        /*
        |--------------------------------------------------------------------------
        | Update Password
        |--------------------------------------------------------------------------
        */

        Route::post('/profile/password', [
            ProfileController::class,
            'updatePassword'
        ])
        ->name('profile.password');


        /*
        |--------------------------------------------------------------------------
        | Hapus Akun Sendiri
        |--------------------------------------------------------------------------
        */

        Route::post('/profile/delete-account', [
            \App\Http\Controllers\Dashboard\AccountController::class,
            'destroy'
        ])
        ->middleware('throttle:5,1')
        ->name('profile.delete-account');

    });
