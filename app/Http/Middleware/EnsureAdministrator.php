<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya Administrator Kantor Pusat (admin perusahaan) yang boleh lewat:
 * profil perusahaan, audit trail, status langganan.
 */
class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || !$user->isCompanyAdmin()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Administrator.');
        }

        return $next($request);
    }
}
