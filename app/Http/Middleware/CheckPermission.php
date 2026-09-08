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

        if (!$user->hasPermission($permission)) {
            abort(
                403,
                'Anda tidak memiliki permission untuk melakukan tindakan ini.'
            );
        }

        return $next($request);
    }
}