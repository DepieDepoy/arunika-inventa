<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login Mobile API
     */
    public function login(Request $request): JsonResponse
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

        $user = User::where($field, $request->login)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => [
                    'Invalid email/WhatsApp number or password.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        */

        if ((int) $user->status !== 1) {
            throw ValidationException::withMessages([
                'login' => [
                    'Your account is inactive.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Password
        |--------------------------------------------------------------------------
        */

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => [
                    'Invalid email/WhatsApp number or password.'
                ],
            ]);
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

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }
}
