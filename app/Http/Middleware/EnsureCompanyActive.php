<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perusahaan yang ditangguhkan dari Backoffice (companies.status = 0)
 * tidak bisa memakai dashboard maupun API mobile.
 */
class EnsureCompanyActive
{
    public const MESSAGE = 'Akun perusahaan Anda sedang ditangguhkan. Silakan hubungi tim Vasetra.';

    public function handle(Request $request, Closure $next): Response
    {
        $isApi = $request->is('api/*');

        if (!$isApi && !$request->is('dashboard', 'dashboard/*')) {
            return $next($request);
        }

        $user = $isApi ? $request->user('sanctum') : $request->user('web');

        if (!$user || !$user->company_id || (int) ($user->company?->status ?? 1) === 1) {
            return $next($request);
        }

        if ($isApi) {
            return response()->json([
                'success' => false,
                'message' => self::MESSAGE,
                'code' => 'COMPANY_SUSPENDED',
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => self::MESSAGE, 'code' => 'COMPANY_SUSPENDED'], 403);
        }

        return redirect()->route('login')->withErrors(['login' => self::MESSAGE]);
    }
}
