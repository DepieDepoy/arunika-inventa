<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $subscription = $user->company?->activeSubscription;

        // Subscription masih aktif
        if ($subscription) {
            return $next($request);
        }

        // Subscription expired / tidak ada subscription aktif
        return redirect()
            ->route('subscription.expired')
            ->with(
                'warning',
                'Subscription perusahaan Anda telah berakhir. Silakan perpanjang subscription.'
            );
    }
}