<?php

namespace App\Http\Middleware;

use App\Support\NotificationText;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa dashboard web mengikuti pilihan user yang login (users.locale),
 * kolom yang sama dengan bahasa aplikasi mobile. Bawaan: Bahasa Indonesia.
 *
 * Hanya untuk user web yang login. Tamu (landing, login, daftar), backoffice
 * dan API tidak disentuh sehingga perilakunya tetap seperti sebelumnya.
 */
class SetUserLocale
{
    public const DEFAULT = 'id';

    public function handle(Request $request, Closure $next): Response
    {
        $backofficePath = trim((string) config('backoffice.path', 'backoffice'), '/');

        if ($request->is('api/*') || $request->is($backofficePath, $backofficePath . '/*')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user) {
            $locale = self::resolve($user->locale);

            App::setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }

    public static function resolve(?string $locale): string
    {
        $locale = strtolower(substr((string) $locale, 0, 2));

        return in_array($locale, NotificationText::LOCALES, true) ? $locale : self::DEFAULT;
    }
}
