<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WebPushSubscription;
use App\Services\Push\PushDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Notifikasi browser (Web Push): simpan / hapus langganan browser user.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Validator manual -> selalu JSON 422 (dipanggil dari JavaScript).
        $validator = Validator::make($request->all(), [
            'endpoint' => ['required', 'url', 'max:2000', 'starts_with:https://'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $user = Auth::user();

        WebPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => WebPushSubscription::hashEndpoint($data['endpoint'])],
            [
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'last_used_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => __('Notifikasi browser aktif.')]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (!is_string($request->input('endpoint')) || $request->input('endpoint') === '') {
            return response()->json(['success' => false, 'message' => __('Endpoint wajib diisi.')], 422);
        }

        WebPushSubscription::query()
            ->where('user_id', Auth::id())
            ->where('endpoint_hash', WebPushSubscription::hashEndpoint((string) $request->input('endpoint')))
            ->delete();

        return response()->json(['success' => true, 'message' => __('Notifikasi browser dimatikan.')]);
    }

    /** Kirim notifikasi uji ke browser & HP milik user sendiri. */
    public function test(): JsonResponse
    {
        PushDispatcher::toUser((int) Auth::id(), [
            'title' => 'Notifikasi Vasetra aktif 🎉',
            'body' => 'Mulai sekarang info request maintenance, jadwal, dan pengingat akan muncul di sini.',
            'url' => url('/dashboard/home'),
            'type' => 'test',
        ]);

        return response()->json(['success' => true, 'message' => __('Notifikasi uji dikirim.')]);
    }
}
