<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        | API Login Rate Limiter
        |--------------------------------------------------------------------------
        |
        | Layer 1:
        | 5 attempts per minute per IP + login identifier.
        |
        | Layer 2:
        | 30 attempts per minute per IP.
        |
        */

        RateLimiter::for('api-login', function (Request $request) {

            $login = strtolower(
                trim((string) $request->input('login'))
            );

            return [
                Limit::perMinute(5)
                    ->by($request->ip() . '|' . $login),

                Limit::perMinute(30)
                    ->by($request->ip()),
            ];
        });
    }
}