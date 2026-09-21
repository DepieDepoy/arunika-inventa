<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | USER BELUM LOGIN
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            /*
            |--------------------------------------------------------------------------
            | API
            |--------------------------------------------------------------------------
            */

            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | WEB / DASHBOARD
            |--------------------------------------------------------------------------
            */

            return redirect()->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK ACTIVE SUBSCRIPTION
        |--------------------------------------------------------------------------
        |
        | activeSubscription() mengecek:
        |
        | - status = active
        | - start_date <= hari ini
        | - end_date >= hari ini
        |
        */

        $subscription = $user->company?->activeSubscription;

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION MASIH AKTIF
        |--------------------------------------------------------------------------
        */

        if ($subscription) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION EXPIRED
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        /*
        |--------------------------------------------------------------------------
        | API - SUBSCRIPTION EXPIRED
        |--------------------------------------------------------------------------
        |
        | Untuk API kita TIDAK boleh redirect ke halaman login/HTML.
        |
        | Non-Administrator:
        | -> 403 JSON
        |
        | Administrator:
        | -> GET / HEAD tetap boleh
        | -> method selain GET/HEAD ditolak
        |
        */

        if ($request->is('api/*')) {

            /*
            |--------------------------------------------------------------------------
            | NON-ADMINISTRATOR
            |--------------------------------------------------------------------------
            */

            if (!$isAdministrator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription has expired.',
                    'code' => 'SUBSCRIPTION_EXPIRED',
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | ADMINISTRATOR - VIEW ONLY
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    strtoupper($request->method()),
                    ['GET', 'HEAD'],
                    true
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription has expired. Please renew your subscription to continue.',
                    'code' => 'SUBSCRIPTION_EXPIRED',
                ], 403);
            }

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | WEB / DASHBOARD
        |--------------------------------------------------------------------------
        |
        | Mulai bagian ini behavior dashboard tetap sama
        | seperti sebelumnya.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | USER NON-ADMINISTRATOR
        |--------------------------------------------------------------------------
        */

        if (!$isAdministrator) {
            return redirect()
                ->route('subscription.expired')
                ->with(
                    'warning',
                    'Subscription perusahaan Anda telah berakhir.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR - VIEW ONLY
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | HALAMAN SUBSCRIPTION
        |--------------------------------------------------------------------------
        |
        | Administrator tetap boleh membuka halaman subscription,
        | termasuk halaman expired.
        |
        */

        if (
            $request->routeIs('subscription.*')
        ) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR HANYA BOLEH GET
        |--------------------------------------------------------------------------
        |
        | GET      = view       -> BOLEH
        | HEAD     = view       -> BOLEH
        |
        | POST     = add/import -> DILARANG
        | PUT      = edit       -> DILARANG
        | PATCH    = edit       -> DILARANG
        | DELETE   = delete     -> DILARANG
        */

        if (
            !in_array(
                strtoupper($request->method()),
                ['GET', 'HEAD'],
                true
            )
        ) {
            abort(
                403,
                'Subscription perusahaan Anda telah berakhir. Silakan lakukan perpanjangan untuk melanjutkan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR EXPIRED - VIEW ONLY
        |--------------------------------------------------------------------------
        */

        return $next($request);
    }
}
