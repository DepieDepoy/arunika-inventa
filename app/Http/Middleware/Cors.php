<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $origin = $request->headers->get('Origin');

        /*
        |--------------------------------------------------------------------------
        | Preflight OPTIONS
        |--------------------------------------------------------------------------
        */

        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Development Flutter Web
        |--------------------------------------------------------------------------
        */

        $isLocalOrigin =
            $origin &&
            (
                str_starts_with(
                    $origin,
                    'http://localhost:'
                ) ||
                str_starts_with(
                    $origin,
                    'http://127.0.0.1:'
                )
            );

        if ($isLocalOrigin) {
            $response->headers->set(
                'Access-Control-Allow-Origin',
                $origin
            );

            $response->headers->set(
                'Vary',
                'Origin'
            );
        }

        $response->headers->set(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS'
        );

        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Requested-With, Accept'
        );

        $response->headers->set(
            'Access-Control-Expose-Headers',
            'Content-Length, Content-Type'
        );

        return $response;
    }
}