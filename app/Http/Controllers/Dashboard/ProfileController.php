<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
        $user = User::query()
            ->with('role')
            ->findOrFail(Auth::id());

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
    public function update(Request $request): JsonResponse
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | USER LOGIN
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->findOrFail(Auth::id());


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

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

                    'name.string' =>
                        'Nama harus berupa teks.',

                    'name.max' =>
                        'Nama maksimal 255 karakter.',


                    'nik.required' =>
                        'NIK wajib diisi.',

                    'nik.string' =>
                        'NIK harus berupa teks.',

                    'nik.max' =>
                        'NIK maksimal 100 karakter.',


                    'email.required' =>
                        'Email wajib diisi.',

                    'email.email' =>
                        'Format email tidak valid.',

                    'email.max' =>
                        'Email maksimal 255 karakter.',


                    'phone.required' =>
                        'No. Telepon wajib diisi.',

                    'phone.string' =>
                        'No. Telepon harus berupa teks.',

                    'phone.max' =>
                        'No. Telepon maksimal 50 karakter.',
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | VALIDATION FAILED
            |--------------------------------------------------------------------------
            */

            if ($validator->fails()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data profile tidak valid.',
                    'errors' => $validator->errors(),
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | DATA VALIDATED
            |--------------------------------------------------------------------------
            */

            $data = $validator->validated();


            /*
            |--------------------------------------------------------------------------
            | TRIM DATA
            |--------------------------------------------------------------------------
            */

            $name = trim($data['name']);
            $nik = trim($data['nik']);
            $phone = trim($data['phone']);
            $email = trim($data['email']);


            /*
            |--------------------------------------------------------------------------
            | NAME TIDAK BOLEH KOSONG SETELAH TRIM
            |--------------------------------------------------------------------------
            */

            if ($name === '') {

                return response()->json([
                    'success' => false,
                    'message' => 'Nama wajib diisi.',
                    'errors' => [
                        'name' => [
                            'Nama wajib diisi.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | NIK TIDAK BOLEH KOSONG SETELAH TRIM
            |--------------------------------------------------------------------------
            */

            if ($nik === '') {

                return response()->json([
                    'success' => false,
                    'message' => 'NIK wajib diisi.',
                    'errors' => [
                        'nik' => [
                            'NIK wajib diisi.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | PHONE TIDAK BOLEH KOSONG SETELAH TRIM
            |--------------------------------------------------------------------------
            */

            if ($phone === '') {

                return response()->json([
                    'success' => false,
                    'message' => 'No. Telepon wajib diisi.',
                    'errors' => [
                        'phone' => [
                            'No. Telepon wajib diisi.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | EMAIL TIDAK BOLEH DIGANTI
            |--------------------------------------------------------------------------
            */

            if ($email !== trim((string) $user->email)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak dapat diubah.',
                    'errors' => [
                        'email' => [
                            'Email tidak dapat diubah dari halaman profile.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | CEK PHONE
            |--------------------------------------------------------------------------
            |
            | Hanya dilakukan kalau nomor telepon memang berubah.
            |
            | Jadi:
            |
            | - Ubah nama saja       -> tidak cek duplicate phone
            | - Ubah NIK saja        -> tidak cek duplicate phone
            | - Phone tetap sama     -> tidak cek duplicate phone
            | - Phone diganti        -> cek ke user lain
            |
            */

            $oldPhone = trim((string) $user->phone);

            if ($phone !== $oldPhone) {

                $phoneExists = User::query()
                    ->where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($phoneExists) {

                    return response()->json([
                        'success' => false,
                        'message' => 'No. Telepon sudah digunakan.',
                        'errors' => [
                            'phone' => [
                                'No. Telepon sudah digunakan oleh user lain.'
                            ],
                        ],
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            $user->name = $name;
            $user->nik = $nik;
            $user->phone = $phone;

            /*
            |--------------------------------------------------------------------------
            | EMAIL SENGAJA TIDAK DIUBAH
            |--------------------------------------------------------------------------
            */

            $user->save();


            /*
            |--------------------------------------------------------------------------
            | RESPONSE SUCCESS
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui.',

                'data' => [
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ],
            ]);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | LOG ERROR
            |--------------------------------------------------------------------------
            */

            report($e);


            /*
            |--------------------------------------------------------------------------
            | RESPONSE ERROR
            |--------------------------------------------------------------------------
            |
            | Saat APP_DEBUG=true, error asli dikirim ke browser
            | supaya mudah mengetahui masalah database/server.
            |
            */

            return response()->json([
                'success' => false,
                'message' => 'Profile gagal diperbarui.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * =========================================================
     * UPDATE PASSWORD
     * =========================================================
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | USER LOGIN
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->findOrFail(Auth::id());


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            $validator = Validator::make(
                $request->all(),
                [
                    'current_password' => [
                        'required',
                        'string',
                    ],

                    'password' => [
                        'required',
                        'string',
                        'min:8',
                    ],

                    'password_confirmation' => [
                        'required',
                        'string',
                    ],
                ],
                [
                    'current_password.required' =>
                        'Password saat ini wajib diisi.',

                    'password.required' =>
                        'Password baru wajib diisi.',

                    'password.min' =>
                        'Password baru minimal 8 karakter.',

                    'password_confirmation.required' =>
                        'Konfirmasi password wajib diisi.',
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | VALIDATION FAILED
            |--------------------------------------------------------------------------
            */

            if ($validator->fails()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Data password tidak valid.',
                    'errors' => $validator->errors(),
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | DATA
            |--------------------------------------------------------------------------
            */

            $data = $validator->validated();


            /*
            |--------------------------------------------------------------------------
            | PASSWORD CONFIRMATION
            |--------------------------------------------------------------------------
            */

            if (
                $data['password'] !==
                $data['password_confirmation']
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Konfirmasi password tidak sesuai.',
                    'errors' => [
                        'password_confirmation' => [
                            'Konfirmasi password tidak sesuai.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK CURRENT PASSWORD
            |--------------------------------------------------------------------------
            */

            if (
                !Hash::check(
                    $data['current_password'],
                    $user->password
                )
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai.',
                    'errors' => [
                        'current_password' => [
                            'Password saat ini tidak sesuai.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | PASSWORD BARU HARUS BERBEDA
            |--------------------------------------------------------------------------
            */

            if (
                Hash::check(
                    $data['password'],
                    $user->password
                )
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Password baru harus berbeda.',
                    'errors' => [
                        'password' => [
                            'Password baru harus berbeda dengan password lama.'
                        ],
                    ],
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE PASSWORD
            |--------------------------------------------------------------------------
            */

            $user->password = Hash::make(
                $data['password']
            );

            $user->save();


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah.',
            ]);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | LOG ERROR
            |--------------------------------------------------------------------------
            */

            report($e);


            /*
            |--------------------------------------------------------------------------
            | RESPONSE ERROR
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => false,
                'message' => 'Password gagal diperbarui.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
