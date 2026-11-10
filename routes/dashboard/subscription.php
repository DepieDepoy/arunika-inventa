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
        // Halaman langganan hanya untuk user Kantor Pusat dengan izin terkait
        // (Administrator cabang tidak memiliki izin subscription.*).
        Route::get('/subscription', [SubscriptionController::class, 'index'])
            ->middleware('permission:subscription.view|subscription.create')
            ->name('subscription.index');

        // Subscription history
        Route::get('/subscription/history', [SubscriptionController::class, 'history'])
            ->middleware('permission:subscription.history')
            ->name('subscription.history');

        // Halaman expired
        Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])
            ->name('subscription.expired');

        // Proses perpanjangan / pilih paket
        Route::post('/subscription/renewmidtrans', [SubscriptionController::class, 'renewmidtrans'])
            ->name('subscription.renewmidtrans');
                
        Route::post('/subscription/renewdoku', [SubscriptionController::class, 'renewdoku'])
            ->name('subscription.renewdoku');

        // Upgrade sekarang (1..N bulan / sampai akhir paket), bayar selisih
        Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgradeNow'])
            ->name('subscription.upgrade');

        // Tambah kapasitas storage / asset / user (prorata, langsung aktif)
        Route::post('/subscription/addon', [SubscriptionController::class, 'buyAddon'])
            ->name('subscription.addon');

        // Batalkan transaksi pembayaran yang belum selesai (lalu bisa buat baru)
        Route::post('/subscription/payment/{paymentTransaction}/cancel', [SubscriptionController::class, 'cancelPendingPayment'])
            ->whereNumber('paymentTransaction')
            ->middleware('permission:subscription.create')
            ->name('subscription.payment.cancel');

        // Halaman setelah pembayaran selesai
        Route::get('/subscription/payment/finish', [SubscriptionController::class, 'paymentFinish'])
            ->name('subscription.payment.finish');

        // Usage & Limits
        Route::get('/subscription/usage', [SubscriptionController::class,'usage'])
            ->middleware('permission:subscription.usage')
            ->name('subscription.usage');
        
        Route::get('/subscription/billing/{paymentTransaction}',[SubscriptionController::class, 'printBilling'])
            ->middleware('permission:subscription.history')
            ->name('subscription.billing.print');

    });