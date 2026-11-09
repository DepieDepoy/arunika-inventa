<?php

namespace App\Http\Middleware;

use App\Support\BranchAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fitur yang datanya masih tingkat perusahaan (Vasetra AI) hanya
 * untuk user kantor pusat, agar user cabang tidak melihat data cabang lain.
 */
class EnsureHeadOfficeUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!BranchAccess::isRestricted($request->user())) {
            return $next($request);
        }

        $message = 'Fitur ini hanya tersedia untuk pengguna kantor pusat. Akun Anda terhubung ke satu cabang.';

        if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message, 'code' => 'BRANCH_RESTRICTED'], 403);
        }

        return redirect()->route('dashboard.home')->with('error', $message);
    }
}
