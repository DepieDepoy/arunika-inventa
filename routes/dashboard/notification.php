<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
Route::prefix('dashboard')
    ->middleware([
        'auth',
        'subscription.access'
    ])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        Route::get('/notifications/{id}', function (string $id) {

            /** @var User|null $user */
            $user = Auth::user();

            // Pastikan user sudah login.
            if (!$user instanceof User) {
                abort(401);
            }

            // Ambil notifikasi milik user yang sedang login.
            $notification = $user->notifications()
                ->where('id', $id)
                ->firstOrFail();

            // Tandai notifikasi sebagai sudah dibaca.
            $notification->markAsRead();

            // Ambil URL tujuan.
            $targetUrl = data_get($notification->data, 'url');

            // Hanya izinkan redirect ke path internal.
            if (
                is_string($targetUrl) &&
                str_starts_with($targetUrl, '/') &&
                !str_starts_with($targetUrl, '//')
            ) {
                return redirect($targetUrl);
            }

            return redirect('/dashboard');

        })->name('dashboard.notifications.read');


        /*
        |--------------------------------------------------------------------------
        | Notifikasi browser (Web Push)
        |--------------------------------------------------------------------------
        */

        Route::post('/push/subscribe', [\App\Http\Controllers\Dashboard\PushSubscriptionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('push.subscribe');

        Route::post('/push/unsubscribe', [\App\Http\Controllers\Dashboard\PushSubscriptionController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('push.unsubscribe');

        Route::post('/push/test', [\App\Http\Controllers\Dashboard\PushSubscriptionController::class, 'test'])
            ->middleware('throttle:5,1')
            ->name('push.test');


    });
