<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        // Mendukung beberapa permission alternatif: permission:asset.view|maintenance.create
        $allowed = collect(explode('|', $permission))
            ->contains(fn (string $code) => $user->hasPermission(trim($code)));

        if (!$allowed) {
            return $this->deny($request);
        }

        return $next($request);
    }

    /**
     * Akses ditolak:
     *  - API mobile          → 403 JSON standar (code FORBIDDEN, lihat bootstrap/app.php)
     *  - AJAX di dashboard   → 403 JSON {success:false, message} untuk popup di halaman
     *  - buka URL langsung   → kembali ke dashboard + popup "tidak punya hak akses"
     *  - kirim form biasa    → kembali ke halaman sebelumnya + popup
     */
    private function deny(Request $request): Response
    {
        $message = 'Anda tidak memiliki hak akses untuk membuka halaman atau melakukan tindakan ini.';

        if ($request->is('api/*')) {
            abort(403, $message);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        // Halaman dashboard tidak dibatasi permission, jadi aman dari redirect berulang.
        $home = route('dashboard.home');

        if ($request->isMethod('GET')) {
            return redirect($home)->with('error', $message);
        }

        return redirect()->back(302, [], $home)->with('error', $message);
    }
}