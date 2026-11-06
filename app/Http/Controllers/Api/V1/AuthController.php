<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\LoginThrottle;
use App\Services\Mobile\AccountPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login Mobile API
     */
    /**
     * Respons saat login dikunci sementara.
     */
    protected function lockoutResponse(LoginThrottle $throttle): JsonResponse
    {
        $seconds = $throttle->lockedSeconds();

        return response()->json([
            'success' => false,
            'message' => $throttle->lockoutMessage(),
            'code' => 'TOO_MANY_LOGIN_ATTEMPTS',
            'retry_after' => $seconds,
        ], 429)->header('Retry-After', (string) $seconds);
    }

    public function login(Request $request, LoginThrottle $throttle, AccountPresenter $presenter): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email or WhatsApp number is required.',
            'password.required' => 'Password is required.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Determine Login Field
        |--------------------------------------------------------------------------
        */

        $field = filter_var(
            $request->login,
            FILTER_VALIDATE_EMAIL
        )
            ? 'email'
            : 'phone';

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Lockout Check
        |--------------------------------------------------------------------------
        */

        if ($throttle->isLocked()) {
            return $this->lockoutResponse($throttle);
        }

        /*
        |--------------------------------------------------------------------------
        | Find User & Verify Password
        |--------------------------------------------------------------------------
        |
        | Akun tidak ditemukan dan password salah diperlakukan sama.
        |
        */

        $user = User::where($field, trim((string) $request->login))->first();

        $passwordValid = $user
            ? Hash::check((string) $request->password, $user->password)
            : false;

        if (!$user) {
            $throttle->burnPasswordCheck((string) $request->password);
        }

        if (!$passwordValid) {
            $throttle->hit();

            if ($user) {
                AuditLogger::log(
                    event: $throttle->isLocked() ? 'login_locked' : 'login_failed',
                    module: 'auth',
                    description: $throttle->isLocked()
                        ? 'Login mobile dikunci sementara karena terlalu banyak percobaan gagal'
                        : 'Login mobile gagal: password salah',
                    subject: $user,
                    companyId: (int) $user->company_id,
                    actor: $user,
                );
            }

            if ($throttle->isLocked()) {
                return $this->lockoutResponse($throttle);
            }

            throw ValidationException::withMessages([
                'login' => [
                    $throttle->failedMessage()
                ],
            ]);
        }

        $throttle->clear();

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        |
        | Baru diinformasikan setelah password terbukti benar.
        |
        */

        if ((int) $user->status !== 1) {
            AuditLogger::log(
                event: 'login_failed',
                module: 'auth',
                description: 'Login mobile ditolak: akun tidak aktif',
                subject: $user,
                companyId: (int) $user->company_id,
                actor: $user,
            );

            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact your administrator.',
                'code' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        // Perusahaan ditangguhkan dari Backoffice Vasetra.
        if ($user->company && (int) $user->company->status !== 1) {
            return response()->json([
                'success' => false,
                'message' => \App\Http\Middleware\EnsureCompanyActive::MESSAGE,
                'code' => 'COMPANY_SUSPENDED',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Email Verification
        |--------------------------------------------------------------------------
        */

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email has not been verified.',
                'code' => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Revoke Existing Mobile Tokens
        |--------------------------------------------------------------------------
        |
        | Untuk sementara kita gunakan satu active token per user/device
        | flow sederhana. Nanti bisa kita kembangkan menjadi multi-device.
        |
        */

        $user->tokens()->delete();

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            Str::limit((string) ($request->input('device_name') ?: 'vasetra-mobile'), 100, '')
        )->plainTextToken;

        $expiration = config('sanctum.expiration');

        /*
        |--------------------------------------------------------------------------
        | Update Last Login
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Device Token (opsional, untuk push notification)
        |--------------------------------------------------------------------------
        */

        if ($request->filled('fcm_token')) {
            DeviceController::registerDevice($user, $request);
        }

        AuditLogger::log(
            event: 'login',
            module: 'auth',
            description: 'Login ke aplikasi mobile',
            subject: $user,
            actor: $user,
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => array_merge([
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $expiration
                    ? now()->addMinutes((int) $expiration)->toIso8601String()
                    : null,
            ], $presenter->present($user)),
        ]);
    }


    /**
     * Current authenticated mobile user.
     *
     * Dipanggil saat aplikasi dibuka untuk menyegarkan profil,
     * permission, dan status langganan.
     */
    public function me(Request $request, AccountPresenter $presenter): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $presenter->present($request->user()),
        ]);
    }


    /**
     * Kirim link reset password ke email (halaman reset ada di web).
     *
     * Respons selalu sama agar tidak bisa dipakai menebak email terdaftar.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        try {
            Password::sendResetLink([
                'email' => strtolower(trim((string) $request->email)),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar, link reset password telah dikirim. Silakan cek inbox atau folder spam.',
        ]);
    }


    /**
     * Logout Mobile
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Delete Current Token
        |--------------------------------------------------------------------------
        */

        $user->currentAccessToken()?->delete();

        // Perangkat ini berhenti menerima push notification untuk akun ini.
        if ($request->filled('fcm_token')) {
            \App\Models\DeviceToken::query()
                ->where('user_id', $user->id)
                ->where('token_hash', \App\Models\DeviceToken::hashToken((string) $request->input('fcm_token')))
                ->delete();
        }

        AuditLogger::log(
            event: 'logout',
            module: 'auth',
            description: 'Logout dari aplikasi mobile',
            subject: $user,
            actor: $user,
        );

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }
}
