<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Kirim push ke aplikasi mobile lewat Firebase Cloud Messaging HTTP v1.
 * Autentikasi memakai service account (JWT RS256 -> OAuth access token), tanpa library tambahan.
 */
class FcmSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public static function credentials(): ?array
    {
        $path = (string) config('push.fcm.credentials');
        if ($path === '') {
            return null;
        }

        $path = is_file($path) ? $path : base_path($path);
        if (!is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) && !empty($json['client_email']) && !empty($json['private_key']) ? $json : null;
    }

    public static function configured(): bool
    {
        return self::credentials() !== null;
    }

    /** Sudah diatur DAN dinyalakan dari Backoffice. */
    public static function active(): bool
    {
        return self::configured() && PushSettings::mobileEnabled();
    }

    /**
     * @return int jumlah perangkat yang berhasil dikirimi
     */
    public function send(int $userId, array $payload): int
    {
        $creds = self::credentials();
        if (!$creds) {
            return 0;
        }

        $tokens = DeviceToken::query()->where('user_id', $userId)->get();
        if ($tokens->isEmpty()) {
            return 0;
        }

        try {
            $accessToken = $this->accessToken($creds);
        } catch (Throwable $e) {
            report($e);

            return 0;
        }

        $project = config('push.fcm.project_id') ?: $creds['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$project}/messages:send";
        $data = array_map('strval', array_filter([
            'type' => $payload['type'] ?? 'general',
            'url' => $payload['url'] ?? '',
            'notification_id' => $payload['id'] ?? '',
        ] + ($payload['data'] ?? []), fn ($v) => $v !== null && !is_array($v)));

        $sent = 0;
        foreach ($tokens as $device) {
            $response = Http::withToken($accessToken)->timeout(10)->post($url, [
                'message' => [
                    'token' => $device->token,
                    'notification' => [
                        'title' => (string) ($payload['title'] ?? 'Vasetra'),
                        'body' => (string) ($payload['body'] ?? ''),
                    ],
                    'data' => (object) $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['channel_id' => 'vasetra_default', 'sound' => 'default'],
                    ],
                    'apns' => [
                        'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $sent++;
                $device->forceFill(['last_used_at' => now()])->save();
                continue;
            }

            $status = (string) data_get($response->json(), 'error.status');
            $code = (string) data_get($response->json(), 'error.details.0.errorCode');

            // Token tidak berlaku lagi (aplikasi di-uninstall / token di-refresh).
            if ($response->status() === 404 || in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true) || $status === 'NOT_FOUND') {
                $device->delete();
            } else {
                Log::info('FCM gagal.', ['status' => $response->status(), 'error' => $status ?: $response->body()]);
            }
        }

        return $sent;
    }

    private function accessToken(array $creds): string
    {
        $key = 'fcm_access_token_' . md5($creds['client_email']);

        return Cache::remember($key, now()->addMinutes(50), function () use ($creds) {
            $tokenUri = $creds['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $now = time();
            $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode([
                'iss' => $creds['client_email'],
                'scope' => self::SCOPE,
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            if (!openssl_sign($unsigned, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Gagal menandatangani token Firebase.');
            }

            $response = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $b64($signature),
            ]);

            if (!$response->successful() || !$response->json('access_token')) {
                throw new RuntimeException('Gagal mendapatkan akses Firebase: ' . $response->body());
            }

            return (string) $response->json('access_token');
        });
    }
}
