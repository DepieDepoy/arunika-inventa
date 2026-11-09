<?php

namespace App\Http\Middleware;

use App\Support\NotificationText;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simpan bahasa pilihan user di aplikasi mobile ke users.locale.
 *
 * Aplikasi mengirim header `X-App-Locale: id|en` di setiap request. Nilainya
 * dipakai untuk bahasa push notification & daftar notifikasi aplikasi.
 * Hanya menulis ke database bila bahasanya berubah.
 */
class RememberAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $header = strtolower(substr((string) $request->header('X-App-Locale'), 0, 2));

        if ($user && in_array($header, NotificationText::LOCALES, true) && $user->locale !== $header) {
            $user->forceFill(['locale' => $header])->saveQuietly();
        }

        return $next($request);
    }
}
