<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckSubscriptionAccess;
use App\Http\Middleware\CheckSubscriptionActive;
use App\Http\Middleware\EnsureUserCompany;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => CheckPermission::class,
            'subscription.access' => CheckSubscriptionAccess::class,
            'subscription.active' => CheckSubscriptionActive::class,
            'api.company' => EnsureUserCompany::class,
            'api.security' => \App\Http\Middleware\ApiSecurityHeaders::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | API JSON Response
        |--------------------------------------------------------------------------
        |
        | Semua request /api/* akan selalu mendapatkan response JSON.
        | Dashboard/web tetap menggunakan response normal Laravel.
        |
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );


        /*
        |--------------------------------------------------------------------------
        | Authentication - 401
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        });


        /*
        |--------------------------------------------------------------------------
        | Authorization - 403
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
                'code' => 'FORBIDDEN',
            ], 403);
        });


        /*
        |--------------------------------------------------------------------------
        | Validation - 422
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });


        /*
        |--------------------------------------------------------------------------
        | Rate Limit - 429
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ThrottleRequestsException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'code' => 'RATE_LIMITED',
            ], 429);
        });


        /*
        |--------------------------------------------------------------------------
        | Not Found - 404
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        });


        /*
        |--------------------------------------------------------------------------
        | Other HTTP Exceptions
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            return response()->json([
                'success' => false,
                'message' => $status >= 500
                    ? 'An internal server error occurred.'
                    : ($e->getMessage() ?: 'An error occurred.'),
                'code' => $status >= 500
                    ? 'SERVER_ERROR'
                    : 'HTTP_ERROR',
            ], $status);
        });


        /*
        |--------------------------------------------------------------------------
        | Unexpected Exception - 500
        |--------------------------------------------------------------------------
        |
        | Jangan pernah mengirim stack trace, file path, line, atau
        | exception detail ke client API.
        |
        */

        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred.',
                'code' => 'SERVER_ERROR',
            ], 500);
        });

    })

    ->create();