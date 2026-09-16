<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        // Halaman subscription
        Route::get('/subscription', [SubscriptionController::class, 'index'])
            ->name('subscription.index');

        // Halaman expired
        Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])
            ->name('subscription.expired');

        // Proses perpanjangan / pilih paket
        Route::post('/subscription/renew', [SubscriptionController::class, 'renew'])
            ->name('subscription.renew');

        Route::get('/subscription/payment/finish', [SubscriptionController::class, 'paymentFinish'])
            ->name('subscription.payment.finish');

    });