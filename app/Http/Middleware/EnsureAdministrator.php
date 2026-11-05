<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya user dengan role Administrator di PT-nya yang boleh lewat.
 */
class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || !$user->isAdministrator()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Administrator.');
        }

        return $next($request);
    }
}
