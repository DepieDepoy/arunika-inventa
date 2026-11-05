<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Dashboard\ProfileController as WebProfileController;
use App\Services\Audit\AuditLogger;
use App\Services\Mobile\AccountPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profil & password user mobile.
 * Aturan validasi memakai controller profil web agar selalu sama.
 */
class ProfileController extends ApiController
{
    public function show(Request $request, AccountPresenter $presenter): JsonResponse
    {
        return $this->ok($presenter->present($request->user()));
    }

    /**
     * Body: name, nik, phone  (email tidak dapat diubah).
     */
    public function update(Request $request, WebProfileController $web, AccountPresenter $presenter): JsonResponse
    {
        // Form web mengirim email (read-only); di mobile cukup diisi otomatis.
        $request->merge(['email' => $request->user()->email]);

        $response = $this->delegate(fn () => $web->update($request), 'PROFILE_INVALID');

        if (!$response->getData(true)['success']) {
            return $response;
        }

        return $this->ok(
            $presenter->present($request->user()->fresh()),
            'Profil berhasil diperbarui.'
        );
    }

    /**
     * Body: current_password, password, password_confirmation.
     * Opsional: logout_other_devices (bool) untuk mencabut sesi perangkat lain.
     */
    public function changePassword(Request $request, WebProfileController $web): JsonResponse
    {
        $response = $this->delegate(fn () => $web->updatePassword($request), 'PASSWORD_INVALID');

        if (!$response->getData(true)['success']) {
            return $response;
        }

        $user = $request->user();

        if ($request->boolean('logout_other_devices')) {
            $currentId = $user->currentAccessToken()?->id;

            $user->tokens()
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->delete();
        }

        AuditLogger::log(
            event: 'password_changed',
            module: 'auth',
            description: 'Mengubah password dari aplikasi mobile',
            subject: $user,
            actor: $user,
        );

        return $this->ok(null, 'Password berhasil diubah.');
    }
}
