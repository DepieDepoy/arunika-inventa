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


    });
