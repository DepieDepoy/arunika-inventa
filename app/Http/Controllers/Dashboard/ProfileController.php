<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAvatar;
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
                        __('Nama wajib diisi.'),

                    'name.string' =>
                        __('Nama harus berupa teks.'),

                    'name.max' =>
                        __('Nama maksimal 255 karakter.'),


                    'nik.required' =>
                        __('NIK wajib diisi.'),

                    'nik.string' =>
                        __('NIK harus berupa teks.'),

                    'nik.max' =>
                        __('NIK maksimal 100 karakter.'),


                    'email.required' =>
                        __('Email wajib diisi.'),

                    'email.email' =>
                        __('Format email tidak valid.'),

                    'email.max' =>
                        __('Email maksimal 255 karakter.'),


                    'phone.required' =>
                        __('No. Telepon wajib diisi.'),

                    'phone.string' =>
                        __('No. Telepon harus berupa teks.'),

                    'phone.max' =>
                        __('No. Telepon maksimal 50 karakter.'),
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
                    'message' => __('Data profile tidak valid.'),
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
                    'message' => __('Nama wajib diisi.'),
                    'errors' => [
                        'name' => [
                            __('Nama wajib diisi.')
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
                    'message' => __('NIK wajib diisi.'),
                    'errors' => [
                        'nik' => [
                            __('NIK wajib diisi.')
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
                    'message' => __('No. Telepon wajib diisi.'),
                    'errors' => [
                        'phone' => [
                            __('No. Telepon wajib diisi.')
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
                    'message' => __('Email tidak dapat diubah.'),
                    'errors' => [
                        'email' => [
                            __('Email tidak dapat diubah dari halaman profile.')
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
                        'message' => __('No. Telepon sudah digunakan.'),
                        'errors' => [
                            'phone' => [
                                __('No. Telepon sudah digunakan oleh user lain.')
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
                'message' => __('Profile berhasil diperbarui.'),

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
                'message' => __('Profile gagal diperbarui.'),
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    /**
     * =========================================================
     * FOTO PROFIL
     * =========================================================
     * Disimpan di documents/{kode}/users (ikut kuota storage).
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            ['photo' => UserAvatar::RULES],
            [
                'photo.required' => __('Pilih foto terlebih dahulu.'),
                'photo.image' => __('File harus berupa gambar.'),
                'photo.mimes' => __('Format foto harus JPG, PNG, atau WEBP.'),
                'photo.max' => __('Ukuran foto maksimal 5 MB.'),
                'photo.uploaded' => __('Foto gagal diunggah. Ukuran maksimal 5 MB.'),
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('photo') ?: 'Foto tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::query()->findOrFail(Auth::id());

        try {
            UserAvatar::store($user, $request->file('photo'));
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['photo' => [$e->getMessage()]],
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('Foto profil gagal disimpan.'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Foto profil berhasil diperbarui.'),
            'data' => ['photo_url' => UserAvatar::url($user)],
        ]);
    }

    /**
     * Ganti bahasa tampilan (id / en). Disimpan di users.locale sehingga
     * berlaku juga untuk email & notifikasi.
     */
    public function updateLocale(Request $request): \Illuminate\Http\RedirectResponse
    {
        $locale = \App\Http\Middleware\SetUserLocale::resolve((string) $request->input('locale'));

        User::query()->whereKey(Auth::id())->update(['locale' => $locale]);

        return redirect()->back();
    }

    public function destroyPhoto(): JsonResponse
    {
        $user = User::query()->findOrFail(Auth::id());

        UserAvatar::remove($user);

        return response()->json([
            'success' => true,
            'message' => __('Foto profil dihapus.'),
            'data' => ['photo_url' => null],
        ]);
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
                        __('Password saat ini wajib diisi.'),

                    'password.required' =>
                        __('Password baru wajib diisi.'),

                    'password.min' =>
                        __('Password baru minimal 8 karakter.'),

                    'password_confirmation.required' =>
                        __('Konfirmasi password wajib diisi.'),
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
                    'message' => __('Data password tidak valid.'),
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
                    'message' => __('Konfirmasi password tidak sesuai.'),
                    'errors' => [
                        'password_confirmation' => [
                            __('Konfirmasi password tidak sesuai.')
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
                    'message' => __('Password saat ini tidak sesuai.'),
                    'errors' => [
                        'current_password' => [
                            __('Password saat ini tidak sesuai.')
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
                    'message' => __('Password baru harus berbeda.'),
                    'errors' => [
                        'password' => [
                            __('Password baru harus berbeda dengan password lama.')
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

            // Password default (mis. hasil import) sudah diganti user.
            $user->must_change_password = false;

            $user->save();


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => __('Password berhasil diubah.'),
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
                'message' => __('Password gagal diperbarui.'),
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
