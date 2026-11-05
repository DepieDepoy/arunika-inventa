<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\LoginThrottle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function login(Request $request, LoginThrottle $throttle): JsonResponse
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
            'vasetra-mobile'
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Update Last Login
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => $request->userAgent(),
        ]);

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
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'nik' => $user->nik,
                    'status' => $user->status,
                    'role' => $user->role?->role_name,
                ],

                'company' => [
                    'id' => $user->company?->id,
                    'name' => $user->company?->company_name,
                    'code' => $user->company?->company_code,
                ],
            ],
        ]);
    }


    /**
     * Current authenticated mobile user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'nik' => $user->nik,
                    'status' => $user->status,
                    'role' => $user->role?->role_name,
                ],

                'company' => [
                    'id' => $user->company?->id,
                    'name' => $user->company?->company_name,
                    'code' => $user->company?->company_code,
                ],
            ],
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
