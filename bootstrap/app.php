<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
//use Throwable;

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

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'permission' => CheckPermission::class,
            'subscription.access' => CheckSubscriptionAccess::class,
            'subscription.active' => CheckSubscriptionActive::class,
            'api.company' => EnsureUserCompany::class,
            'api.security' => \App\Http\Middleware\ApiSecurityHeaders::class,
            'api.locale' => \App\Http\Middleware\RememberAppLocale::class,
            'administrator' => \App\Http\Middleware\EnsureAdministrator::class,
            'head-office' => \App\Http\Middleware\EnsureHeadOfficeUser::class,
            'backoffice' => \App\Http\Middleware\EnsureBackofficeAdmin::class,
            'track' => \App\Http\Middleware\TrackPageView::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUDIT TRAIL - export / cetak data
        |--------------------------------------------------------------------------
        */

        $middleware->web(append: [
            \App\Http\Middleware\AuditDataAccess::class,
            \App\Http\Middleware\WebSecurityHeaders::class,
            \App\Http\Middleware\EnsureCompanyActive::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // Perusahaan ditangguhkan & password awal juga berlaku untuk API mobile.
        $middleware->api(append: [
            \App\Http\Middleware\CheckAppVersion::class,
            \App\Http\Middleware\EnsureCompanyActive::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // CORS diatur oleh HandleCors bawaan Laravel lewat config/cors.php.
    })

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    */

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | API JSON Response
        |--------------------------------------------------------------------------
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
                // Kode seragam agar aplikasi mobile cukup memeriksa `code`.
                'code' => match (true) {
                    $status >= 500 => 'SERVER_ERROR',
                    $status === 401 => 'UNAUTHENTICATED',
                    $status === 403 => 'FORBIDDEN',
                    $status === 404 => 'NOT_FOUND',
                    $status === 405 => 'METHOD_NOT_ALLOWED',
                    $status === 429 => 'RATE_LIMITED',
                    default => 'HTTP_ERROR',
                },
            ], $status);
        });


        /*
        |--------------------------------------------------------------------------
        | Unexpected Exception - 500
        |--------------------------------------------------------------------------
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