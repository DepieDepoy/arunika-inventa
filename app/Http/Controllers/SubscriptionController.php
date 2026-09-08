<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function expired()
    {
        $user = Auth::user();

        $subscription = $user->company
            ?->subscriptions()
            ->latest('end_date')
            ->first();

        return view(
            'dashboard.subscription.expired',
            compact('subscription')
        );
    }
}