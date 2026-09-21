<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCompany
{
    /**
     * Ensure authenticated user belongs to a company.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | User must belong to a company
        |--------------------------------------------------------------------------
        */

        if (!$user || !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'User account is not associated with a company.',
                'code' => 'COMPANY_NOT_FOUND',
            ], 403);
        }

        return $next($request);
    }
}