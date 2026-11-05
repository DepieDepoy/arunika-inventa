<?php

namespace App\Providers;

use App\Models\ImportHistory;
use App\Services\Audit\AuthEventSubscriber;
use App\Services\Audit\ImportHistoryAuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Audit Trail
        |--------------------------------------------------------------------------
        */

        Event::subscribe(AuthEventSubscriber::class);

        ImportHistory::observe(ImportHistoryAuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | API Login Rate Limiter
        |--------------------------------------------------------------------------
        |
        | Pengaman anti-spam request (burst).
        |
        | Penguncian akun setelah N kali gagal ditangani oleh
        | App\Services\Auth\LoginThrottle (config/auth.php -> login_throttle).
        | Limit di sini sengaja lebih longgar agar pesan lockout
        | dari LoginThrottle yang tampil ke user.
        |
        | Layer 1:
        | 10 requests per minute per IP + login identifier.
        |
        | Layer 2:
        | 30 requests per minute per IP.
        |
        */

        RateLimiter::for('api-login', function (Request $request) {

            $login = strtolower(
                trim((string) $request->input('login'))
            );

            return [
                Limit::perMinute(10)
                    ->by($request->ip() . '|' . $login),

                Limit::perMinute(30)
                    ->by($request->ip()),
            ];
        });
    }
}