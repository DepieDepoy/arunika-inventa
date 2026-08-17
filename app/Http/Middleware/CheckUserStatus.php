<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->status == 0) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'login' => 'Akun Anda sudah tidak aktif.',
                ]);
        }

        return $next($request);
    }
}