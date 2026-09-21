<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Halaman pemberitahuan verifikasi email.
     */
    public function notice(Request $request): View
    {
        return view('auth.verify-email', [
            'email' => session('verification_email'),
        ]);
    }

    /**
     * Proses verifikasi email.
     */
    public function verify(
        Request $request,
        int $id,
        string $hash
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | 1. Validasi signed URL
        |--------------------------------------------------------------------------
        */
        if (!URL::hasValidSignature($request)) {
            abort(
                403,
                'Link verifikasi email sudah tidak valid atau sudah kedaluwarsa.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Ambil user
        |--------------------------------------------------------------------------
        */
        $user = User::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | 3. Validasi hash email
        |--------------------------------------------------------------------------
        */
        $emailHash = sha1(
            $user->getEmailForVerification()
        );

        if (!hash_equals($emailHash, $hash)) {
            abort(
                403,
                'Link verifikasi email tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Tandai email sebagai verified
        |--------------------------------------------------------------------------
        */
        if (!$user->hasVerifiedEmail()) {

            $user->forceFill([
                'email_verified_at' => now(),
            ]);

            $user->save();

            /*
            |--------------------------------------------------------------------------
            | Event Verified
            |--------------------------------------------------------------------------
            */
            event(new Verified($user));
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Bersihkan session verification
        |--------------------------------------------------------------------------
        */
        session()->forget([
            'verification_user_id',
            'verification_email',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. Redirect ke login
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route('login')
            ->with(
                'success',
                'Email berhasil diverifikasi! Silakan login menggunakan akun Anda.'
            );
    }

    /**
     * Kirim ulang email verifikasi.
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = session('verification_user_id');

        /*
        |--------------------------------------------------------------------------
        | Tidak ada session verification
        |--------------------------------------------------------------------------
        */
        if (!$userId) {
            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'Verification session has expired. Please register again.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil user
        |--------------------------------------------------------------------------
        */
        $user = User::find($userId);

        if (!$user) {
            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'Account data could not be found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Sudah diverifikasi
        |--------------------------------------------------------------------------
        */
        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with(
                    'success',
                    'Your email has already been verified. Please sign in.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Kirim ulang email
        |--------------------------------------------------------------------------
        */
        $user->sendEmailVerificationNotification();

        return back()->with(
            'success',
            'A new verification link has been sent to your email.'
        );
    }
}