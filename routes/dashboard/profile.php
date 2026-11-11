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

        // Bahasa tampilan (users.locale, juga dipakai aplikasi mobile & email).
        Route::post('/profile/locale', [
            ProfileController::class,
            'updateLocale'
        ])
        ->middleware('throttle:30,1')
        ->name('profile.locale');

        Route::post('/profile/photo', [
            ProfileController::class,
            'updatePhoto'
        ])
        ->middleware('throttle:20,1')
        ->name('profile.photo.update');

        Route::delete('/profile/photo', [
            ProfileController::class,
            'destroyPhoto'
        ])
        ->name('profile.photo.destroy');



        Route::post('/profile/delete-account', [
            \App\Http\Controllers\Dashboard\AccountController::class,
            'destroy'
        ])
        ->middleware('throttle:5,1')
        ->name('profile.delete-account');

    });
