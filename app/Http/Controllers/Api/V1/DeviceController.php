<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Registrasi token perangkat (Firebase Cloud Messaging) untuk push notification.
 *
 * Flutter memanggil POST /devices setelah login dan setiap kali
 * FirebaseMessaging.onTokenRefresh terpicu, lalu DELETE /devices saat logout.
 */
class DeviceController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(self::rules());

        $device = self::registerDevice($request->user(), $request);

        return $this->ok([
            'device' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'device_name' => $device->device_name,
                'last_used_at' => $this->dateTime($device->last_used_at),
            ],
        ], 'Perangkat terdaftar untuk notifikasi.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token_hash', DeviceToken::hashToken((string) $request->fcm_token))
            ->delete();

        return $this->ok(null, 'Perangkat dihapus dari notifikasi.');
    }

    public static function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Simpan / pindahkan token ke user yang sedang login.
     * Dipakai juga oleh AuthController@login bila fcm_token dikirim.
     */
    public static function registerDevice(User $user, Request $request): DeviceToken
    {
        $token = (string) $request->input('fcm_token');
        $platform = in_array($request->input('platform'), ['android', 'ios'], true)
            ? $request->input('platform')
            : 'android';

        return DeviceToken::updateOrCreate(
            ['token_hash' => DeviceToken::hashToken($token)],
            [
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'token' => $token,
                'platform' => $platform,
                'device_name' => Str::limit((string) $request->input('device_name'), 150, '') ?: null,
                'app_version' => Str::limit((string) $request->input('app_version'), 30, '') ?: null,
                'last_used_at' => now(),
            ]
        );
    }
}
