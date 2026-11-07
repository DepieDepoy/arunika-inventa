<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Intelligence\TeraSettings;
use App\Services\Mobile\MobileAppSettings;
use App\Services\Push\FcmSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Info aplikasi (tanpa login). Dipanggil saat aplikasi dibuka:
 * cek wajib update, mode pemeliharaan, tautan legal, dan fitur yang aktif.
 *
 * GET /app-info?platform=android&version=1.0.0
 * (atau header X-App-Platform / X-App-Version)
 */
class AppInfoController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $platform = strtolower((string) ($request->query('platform') ?: $request->header('X-App-Platform') ?: 'android'));
        $version = (string) ($request->query('version') ?: $request->header('X-App-Version'));
        $settings = MobileAppSettings::all();
        $info = MobileAppSettings::forPlatform($platform);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => 'Vasetra',
                'api_version' => 'v1',
                'platform' => in_array($platform, MobileAppSettings::PLATFORMS, true) ? $platform : 'android',
                'min_app_version' => $info['min_version'],
                'latest_app_version' => $info['latest_version'],
                'store_url' => [
                    'android' => $settings['android_store_url'],
                    'ios' => $settings['ios_store_url'],
                ],
                'update' => [
                    'required' => MobileAppSettings::mustUpdate($platform, $version),
                    'available' => $version !== '' && version_compare($version, $info['latest_version'], '<'),
                    'message' => $settings['update_message'],
                ],
                'maintenance' => [
                    'active' => (bool) $settings['maintenance'],
                    'message' => $settings['maintenance_message'],
                ],
                'features' => [
                    'push_notification' => FcmSender::active(),
                    'tera_ai' => TeraSettings::enabled(),
                ],
                'links' => [
                    'privacy_policy' => route('legal.privacy'),
                    'terms_of_service' => route('legal.terms'),
                    'delete_account' => route('account-deletion.create'),
                    'web' => url('/'),
                ],
                'support_email' => config('mobile.support_email') ?: 'info@vasetra.com',
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
