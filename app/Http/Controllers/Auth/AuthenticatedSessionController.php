<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\LoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login.index');
    }

    public function store(Request $request, LoginThrottle $throttle): RedirectResponse
    {
        $request->validate(
            [
                'login' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ],
            [
                'login.required' => 'Email address or WhatsApp number is required.',
                'password.required' => 'Password is required.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | LOCKOUT CHECK
        |--------------------------------------------------------------------------
        |
        | Dicek sebelum query user & cek password, sehingga saat terkunci
        | password tidak diperiksa sama sekali (benar ataupun salah).
        |
        */

        if ($throttle->isLocked()) {
            return back()
                ->withErrors(['login' => $throttle->lockoutMessage()])
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | DETERMINE LOGIN FIELD
        |--------------------------------------------------------------------------
        */

        $login = trim((string) $request->input('login'));

        $field = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        /*
        |--------------------------------------------------------------------------
        | FIND USER & VERIFY PASSWORD
        |--------------------------------------------------------------------------
        |
        | Akun tidak ditemukan dan password salah diperlakukan sama
        | (pesan & waktu respons), agar tidak bisa dipakai untuk
        | mengecek email/no. HP yang terdaftar.
        |
        */

        $user = User::where($field, $login)->first();

        $passwordValid = $user
            ? Hash::check((string) $request->password, $user->password)
            : false;

        if (!$user) {
            $throttle->burnPasswordCheck((string) $request->password);
        }

        if (!$passwordValid) {
            $throttle->hit();

            // Audit hanya bila akun ada (audit terikat ke PT).
            if ($user) {
                AuditLogger::log(
                    event: $throttle->isLocked() ? 'login_locked' : 'login_failed',
                    module: 'auth',
                    description: $throttle->isLocked()
                        ? 'Login dikunci sementara karena terlalu banyak percobaan gagal'
                        : 'Login gagal: password salah',
                    subject: $user,
                    companyId: (int) $user->company_id,
                    actor: $user,
                );
            }

            return back()
                ->withErrors([
                    'login' => $throttle->isLocked()
                        ? $throttle->lockoutMessage()
                        : $throttle->failedMessage(),
                ])
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD BENAR -> RESET HITUNGAN GAGAL
        |--------------------------------------------------------------------------
        */

        $throttle->clear();

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT STATUS
        |--------------------------------------------------------------------------
        |
        | Baru diinformasikan setelah password terbukti benar.
        |
        */

        if ((int) $user->status !== 1) {
            AuditLogger::log(
                event: 'login_failed',
                module: 'auth',
                description: 'Login ditolak: akun tidak aktif',
                subject: $user,
                companyId: (int) $user->company_id,
                actor: $user,
            );

            return back()
                ->withErrors([
                    'login' => 'Your account is inactive. Please contact your administrator.'
                ])
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL VERIFICATION
        |--------------------------------------------------------------------------
        |
        | Password benar tetapi email belum diverifikasi.
        | JANGAN login ke dashboard.
        |
        */

        if (!$user->hasVerifiedEmail()) {

            session([
                'verification_user_id' => $user->id,
                'verification_email' => $user->email,
            ]);

            return back()
                ->with(
                    'error',
                    'Email Anda belum diverifikasi. Silakan cek inbox email Anda terlebih dahulu.'
                )
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN
        |--------------------------------------------------------------------------
        */

        Auth::login(
            $user,
            $request->boolean('remember')
        );

        /*
        |--------------------------------------------------------------------------
        | UPDATE LAST LOGIN
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | REGENERATE SESSION
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        $subscription = $user->company?->activeSubscription;

        if ($subscription) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR BYPASS
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if ($isAdministrator) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION EXPIRED
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('subscription.expired')
            ->with(
                'warning',
                'Subscription perusahaan Anda telah berakhir. Silakan hubungi Administrator untuk melakukan perpanjangan.'
            );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}