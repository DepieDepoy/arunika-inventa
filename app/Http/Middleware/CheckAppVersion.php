<?php

namespace App\Http\Middleware;

use App\Services\Mobile\MobileAppSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplikasi mobile mengirim header:
 *   X-App-Platform: android | ios
 *   X-App-Version : 1.2.0
 * Versi di bawah minimal -> 426 UPGRADE_REQUIRED (aplikasi tampilkan layar update).
 * Mode pemeliharaan aktif -> 503 MAINTENANCE.
 * Request tanpa header (web / Postman) tidak diperiksa.
 */
class CheckAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('api/*') || $request->routeIs('api.v1.app-info')) {
            return $next($request);
        }

        $platform = strtolower((string) $request->header('X-App-Platform'));
        $version = (string) $request->header('X-App-Version');

        if ($platform === '' || $version === '') {
            return $next($request);
        }

        $settings = MobileAppSettings::all();

        if (!empty($settings['maintenance'])) {
            return response()->json([
                'success' => false,
                'message' => $settings['maintenance_message'],
                'code' => 'MAINTENANCE',
            ], 503);
        }

        if (MobileAppSettings::mustUpdate($platform, $version)) {
            $info = MobileAppSettings::forPlatform($platform);

            return response()->json([
                'success' => false,
                'message' => $settings['update_message'],
                'code' => 'UPGRADE_REQUIRED',
                'data' => $info,
            ], 426);
        }

        return $next($request);
    }
}
