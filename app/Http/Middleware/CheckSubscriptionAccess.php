<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionAccess
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
        /*$subscription = $user->company
                        ?->subscriptions()
                        ->where('status', 'active')
                        ->whereDate('start_date', '<=', today())
                        ->whereDate('end_date', '>=', today())
                        ->latest('end_date')
                        ->first();*/

        // Subscription masih aktif
        if ($subscription) {
            return $next($request);
        }

        // Administrator tetap boleh masuk untuk view
        if (
            $user->role &&
            $user->role->role_code === 'administrator'
        ) {
            return $next($request);
        }

        // User selain Administrator diarahkan ke halaman subscription
        return redirect()
            ->route('subscription.expired')
            ->with(
                'warning',
                'Subscription perusahaan Anda telah berakhir.'
            );
    }
}