<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Hapus akun sendiri dari dashboard web (menu Profil).
 */
class AccountController extends Controller
{
    public function destroy(Request $request, AccountDeletionService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
            'confirm_text' => ['required', 'in:HAPUS'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'password.required' => __('Password wajib diisi.'),
            'confirm_text.in' => __('Ketik HAPUS (huruf besar) untuk konfirmasi.'),
            'confirm_text.required' => __('Ketik HAPUS (huruf besar) untuk konfirmasi.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        if (!Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json(['success' => false, 'errors' => ['password' => [__('Password salah.')]]], 422);
        }

        $result = $service->deleteSelf($user, 'web', $request->input('reason'), $request->ip());

        if ($result['status'] === AccountDeletionService::DONE) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'message' => $result['message'],
            'redirect' => $result['status'] === AccountDeletionService::DONE ? route('login') : null,
        ]);
    }
}
