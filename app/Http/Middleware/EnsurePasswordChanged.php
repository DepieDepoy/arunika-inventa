<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User dengan password awal (dibuat admin / hasil import) wajib menggantinya
 * sebelum memakai dashboard atau API mobile. Halaman profil, logout, dan
 * notifikasi tetap bisa diakses.
 */
class EnsurePasswordChanged
{
    /** Route yang tetap boleh diakses selama password belum diganti. */
    private const ALLOWED = [
        'profile.*',
        'logout',
        'verification.*',
        'password.*',
        'api.v1.profile.*',
        'api.v1.account.*',
        'api.v1.app-info',
        'api.v1.logout',
        'api.v1.devices.*',
        'api.v1.app-info',
        'api.v1.notifications.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $isApi = $request->is('api/*');

        // Web: hanya area dashboard pelanggan (bukan landing / backoffice).
        if (!$isApi && !$request->is('dashboard', 'dashboard/*')) {
            return $next($request);
        }

        $user = $isApi ? $request->user('sanctum') : $request->user('web');

        if (!$user || !$user->must_change_password || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        $message = 'Silakan ganti password awal Anda terlebih dahulu.';

        if ($isApi || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => 'PASSWORD_CHANGE_REQUIRED',
            ], 403);
        }

        return redirect()->route('profile.index')->with('warning', $message);
    }
}
