<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * =========================================================
     * PROFILE PAGE
     * =========================================================
     */
    public function index()
    {
        // =====================================================
        // AMBIL USER YANG SEDANG LOGIN
        // =====================================================

        $userId = Auth::id();

        $user = User::findOrFail($userId);


        // =====================================================
        // TAMPILKAN PROFILE
        // =====================================================

        return view(
            'dashboard.profile.index',
            compact('user')
        );
    }


    /**
     * =========================================================
     * UPDATE PROFILE
     * =========================================================
     */
    public function update(Request $request)
    {
        // =====================================================
        // AMBIL USER YANG SEDANG LOGIN
        // =====================================================

        $userId = Auth::id();

        $user = User::findOrFail($userId);


        // =====================================================
        // VALIDASI
        // =====================================================

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'nik' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'phone' => [
                    'required',
                    'string',
                    'max:50',
                ],
            ],
            [
                'name.required' =>
                    'Nama wajib diisi.',

                'name.max' =>
                    'Nama maksimal 255 karakter.',

                'nik.required' =>
                    'NIK wajib diisi.',

                'email.required' =>
                    'Email wajib diisi.',

                'email.email' =>
                    'Format email tidak valid.',

                'phone.required' =>
                    'No. Telepon wajib diisi.',

                'phone.max' =>
                    'No. Telepon maksimal 50 karakter.',
            ]
        );


        // =====================================================
        // JIKA VALIDASI GAGAL
        // =====================================================

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }


        // =====================================================
        // EMAIL TIDAK BOLEH DIUBAH
        // =====================================================

        if ($request->email !== $user->email) {

            return response()->json([
                'success' => false,

                'errors' => [
                    'email' => [
                        'Email tidak dapat diubah dari halaman profile.'
                    ],
                ],
            ], 422);
        }


        // =====================================================
        // CEK PHONE DUPLIKAT
        // HANYA DALAM COMPANY YANG SAMA
        // =====================================================

        $phoneExists = User::query()
            ->where('company_id', $user->company_id)
            ->where('phone', $request->phone)
            ->where('id', '!=', $user->id)
            ->exists();


        if ($phoneExists) {

            return response()->json([
                'success' => false,

                'errors' => [
                    'phone' => [
                        'No. Telepon sudah digunakan oleh user lain.'
                    ],
                ],
            ], 422);
        }


        // =====================================================
        // UPDATE PROFILE
        // =====================================================

        $user->name = $request->name;

        $user->nik = $request->nik;

        // Email sengaja tidak diubah
        // $user->email = $request->email;

        $user->phone = $request->phone;


        // =====================================================
        // SIMPAN
        // =====================================================

        $user->save();


        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui.',
        ]);
    }


    /**
     * =========================================================
     * UPDATE PASSWORD
     * =========================================================
     */
    public function updatePassword(Request $request)
    {
        // =====================================================
        // AMBIL USER YANG SEDANG LOGIN
        // =====================================================

        $userId = Auth::id();

        $user = User::findOrFail($userId);


        // =====================================================
        // VALIDASI
        // =====================================================

        $validator = Validator::make(
            $request->all(),
            [
                'current_password' => [
                    'required',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'same:password_confirmation',
                ],

                'password_confirmation' => [
                    'required',
                    'same:password',
                ],
            ],
            [
                'current_password.required' =>
                    'Password saat ini wajib diisi.',

                'password.required' =>
                    'Password baru wajib diisi.',

                'password.min' =>
                    'Password baru minimal 8 karakter.',

                'password.same' =>
                    'Password baru dan konfirmasi password harus sama.',

                'password_confirmation.required' =>
                    'Konfirmasi password wajib diisi.',

                'password_confirmation.same' =>
                    'Konfirmasi password tidak sesuai.',
            ]
        );


        // =====================================================
        // JIKA VALIDASI GAGAL
        // =====================================================

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }


        // =====================================================
        // CEK PASSWORD LAMA
        // =====================================================

        $currentPasswordCorrect = Hash::check(
            $request->current_password,
            $user->password
        );


        if (!$currentPasswordCorrect) {

            return response()->json([
                'success' => false,

                'errors' => [
                    'current_password' => [
                        'Password saat ini tidak sesuai.'
                    ],
                ],
            ], 422);
        }


        // =====================================================
        // PASSWORD BARU TIDAK BOLEH SAMA
        // DENGAN PASSWORD LAMA
        // =====================================================

        $newPasswordSameAsOld = Hash::check(
            $request->password,
            $user->password
        );


        if ($newPasswordSameAsOld) {

            return response()->json([
                'success' => false,

                'errors' => [
                    'password' => [
                        'Password baru harus berbeda dengan password lama.'
                    ],
                ],
            ], 422);
        }


        // =====================================================
        // SIMPAN PASSWORD BARU
        // =====================================================

        $user->password = Hash::make(
            $request->password
        );


        $user->save();


        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}