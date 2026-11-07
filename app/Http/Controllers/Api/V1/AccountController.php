<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Account\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Hapus akun dari aplikasi mobile (wajib Google Play & App Store).
 *
 * POST /account/delete  body: password, reason (opsional)
 *   status = deleted          -> akun sudah dihapus, token dicabut; aplikasi kembali ke layar login.
 *   status = company_pending  -> administrator terakhir; permintaan penutupan perusahaan dicatat.
 */
class AccountController extends ApiController
{
    public function destroy(Request $request, AccountDeletionService $service): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if (!Hash::check((string) $request->input('password'), $user->password)) {
            return $this->fail('Password salah.', 'INVALID_PASSWORD', 422, ['password' => ['Password salah.']]);
        }

        $result = $service->deleteSelf($user, 'app', $request->input('reason'), $request->ip());

        return $this->ok([
            'status' => $result['status'],
            'request_id' => $result['request']->id,
        ], $result['message']);
    }
}
