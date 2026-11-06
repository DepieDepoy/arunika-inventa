<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya pegawai Vasetra (guard "backoffice") yang boleh membuka backoffice.
 * Login pelanggan (guard "web") tidak berlaku di sini.
 */
class EnsureBackofficeAdmin
{
    /**
     * Pemakaian: 'backoffice' (semua pegawai) atau 'backoffice:super' (Super Admin saja).
     */
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $admin = Auth::guard('backoffice')->user();

        if (!$admin) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('backoffice.login'));
        }

        if (!$admin->is_active) {
            Auth::guard('backoffice')->logout();

            return redirect()->route('backoffice.login')
                ->withErrors(['email' => 'Akun backoffice Anda sudah dinonaktifkan.']);
        }

        Auth::shouldUse('backoffice');

        if ($level === 'super' && !$admin->isSuperAdmin()) {
            abort(403, 'Halaman ini khusus Super Admin backoffice.');
        }

        return $next($request);
    }
}
