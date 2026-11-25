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
        // Paginasi Bootstrap 5 ringkas (bawaan Laravel = Tailwind -> panah SVG raksasa).
        \Illuminate\Pagination\Paginator::defaultView('partials.pagination');
        \Illuminate\Pagination\Paginator::defaultSimpleView('partials.pagination-simple');

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

        /*
        |--------------------------------------------------------------------------
        | FORM PUBLIK WEB (anti bot / spam email)
        |--------------------------------------------------------------------------
        |
        | Pendaftaran mengirim email ke alamat yang diisi, sehingga bisa disalahgunakan
        | untuk spam. Batas per IP + batas GLOBAL (menahan botnet yang berganti IP).
        |
        */

        // Batas PERCOBAAN kirim form daftar (termasuk yang salah isi), cukup longgar
        // agar orang yang salah ketik tidak terkunci. Batas pendaftaran yang BERHASIL
        // (yang benar-benar membuat PT & mengirim email) diatur lebih ketat di
        // RegisteredUserController (RegistrationQuota).
        RateLimiter::for('web-register', fn (Request $request) => [
            Limit::perMinutes(10, 15)->by('register-ip:' . $request->ip()),
            Limit::perDay(60)->by('register-ip-day:' . $request->ip()),
            Limit::perMinute(60)->by('register-global'),
        ]);

        RateLimiter::for('web-password-email', fn (Request $request) => [
            Limit::perMinutes(10, 5)->by('pwd-ip:' . $request->ip()),
            Limit::perMinute(20)->by('pwd-global'),
        ]);

        RateLimiter::for('web-login', fn (Request $request) => [
            Limit::perMinute(20)->by('login-ip:' . $request->ip()),
        ]);
    }
}