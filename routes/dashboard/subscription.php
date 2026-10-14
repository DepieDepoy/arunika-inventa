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

        // Halaman subscription / pilih paket
        Route::get('/subscription', [SubscriptionController::class, 'index'])
            ->name('subscription.index');

        // Subscription history
        Route::get('/subscription/history', [SubscriptionController::class, 'history'])
            ->name('subscription.history');

        // Halaman expired
        Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])
            ->name('subscription.expired');

        // Proses perpanjangan / pilih paket
        Route::post('/subscription/renewmidtrans', [SubscriptionController::class, 'renewmidtrans'])
            ->name('subscription.renewmidtrans');
                
            Route::post('/subscription/renewdoku', [SubscriptionController::class, 'renewdoku'])
                ->name('subscription.renewdoku');

        // Halaman setelah pembayaran selesai
        Route::get('/subscription/payment/finish', [SubscriptionController::class, 'paymentFinish'])
            ->name('subscription.payment.finish');

    });