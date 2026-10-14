<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityHeaders
{
    /**
     * Add security headers to API responses.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | Prevent MIME type sniffing
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent framing / clickjacking
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'X-Frame-Options',
            'DENY'
        );

        /*
        |--------------------------------------------------------------------------
        | Referrer policy
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'Referrer-Policy',
            'no-referrer'
        );

        /*
        |--------------------------------------------------------------------------
        | Browser permissions policy
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        /*
        |--------------------------------------------------------------------------
        | Content Security Policy
        |--------------------------------------------------------------------------
        |
        | API hanya mengembalikan JSON.
        |
        */

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none';"
        );

        return $response;
    }
}
