<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua halaman web (landing, dashboard, backoffice).
 */
class WebSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        // Halaman tidak boleh disisipkan (iframe) di situs lain -> cegah clickjacking.
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS hanya saat HTTPS di produksi (jangan aktif di lokal / http).
        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman setelah login jangan disimpan cache browser / proxy.
        if ($request->is('dashboard', 'dashboard/*', trim(config('backoffice.path', 'backoffice'), '/') . '*')
            && !$headers->has('Content-Disposition')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
