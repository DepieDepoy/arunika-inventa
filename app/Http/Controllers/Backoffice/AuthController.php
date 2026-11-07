<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BackofficeAdmin;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Backoffice\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login backoffice pegawai Vasetra (guard "backoffice").
 */
class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('backoffice')->check()) {
            return redirect()->route('backoffice.home');
        }

        return view('backoffice.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'backoffice-login:' . Str::lower($credentials['email']) . '|' . $request->ip();
        $max = (int) config('backoffice.max_login_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        $admin = BackofficeAdmin::query()->where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password) || !$admin->is_active) {
            RateLimiter::hit($key, (int) config('backoffice.lockout_minutes', 15) * 60);

            if ($admin) {
                BackofficeAudit::log('login_failed', 'Gagal login backoffice: ' . $admin->email, admin: $admin);
            }

            throw ValidationException::withMessages([
                'email' => $admin && !$admin->is_active
                    ? 'Akun backoffice ini sudah dinonaktifkan.'
                    : 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($key);

        // Verifikasi 2 langkah: password benar -> minta kode authenticator dulu
        // (kecuali perangkat ini sudah dipercaya 30 hari).
        if (TwoFactor::mustChallenge($admin) && !TwoFactor::isTrusted($request, $admin)) {
            $request->session()->regenerate();
            $request->session()->put('bo_2fa_pending', [
                'id' => $admin->id,
                'remember' => $request->boolean('remember'),
                'expires' => now()->addMinutes(5)->timestamp,
                'tries' => 0,
            ]);

            return redirect()->route('backoffice.two-factor.challenge');
        }

        return $this->completeLogin($request, $admin, $request->boolean('remember'));
    }

    /** Halaman memasukkan kode 6 digit. */
    public function showChallenge(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);
        if (!$pending) {
            return redirect()->route('backoffice.login')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan login ulang.']);
        }

        return view('backoffice.auth.two-factor-challenge', [
            'email' => TwoFactor::maskEmail(BackofficeAdmin::query()->find($pending['id'])?->email ?? ''),
        ]);
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);
        $admin = $pending ? BackofficeAdmin::query()->find($pending['id']) : null;

        if (!$admin || !$admin->is_active) {
            $request->session()->forget('bo_2fa_pending');

            return redirect()->route('backoffice.login')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan login ulang.']);
        }

        $code = (string) $request->input('code', '');
        $recovery = (string) $request->input('recovery_code', '');
        $secret = TwoFactor::secretOf($admin);

        $ok = $recovery !== ''
            ? TwoFactor::useRecoveryCode($admin, $recovery)
            : ($secret && TwoFactor::verify($secret, $code, $admin->id));

        if (!$ok) {
            $pending['tries']++;
            BackofficeAudit::log('two_factor_failed', 'Kode verifikasi 2 langkah salah', admin: $admin);

            // Maksimal 5 kali salah, lalu harus login ulang dari awal.
            if ($pending['tries'] >= 5) {
                $request->session()->forget('bo_2fa_pending');

                return redirect()->route('backoffice.login')->withErrors(['email' => 'Terlalu banyak kode salah. Silakan login ulang.']);
            }

            $request->session()->put('bo_2fa_pending', $pending);

            return back()->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa. Sisa percobaan: ' . (5 - $pending['tries']) . '.']);
        }

        $request->session()->forget('bo_2fa_pending');

        if ($recovery !== '') {
            BackofficeAudit::log('two_factor_recovery_used', 'Login memakai kode cadangan (sisa ' . TwoFactor::recoveryCodesLeft($admin) . ')', admin: $admin);
        }

        $response = $this->completeLogin($request, $admin, (bool) $pending['remember']);

        if ($request->boolean('trust_device')) {
            $response->withCookie(cookie(TwoFactor::TRUST_COOKIE, TwoFactor::trustValue($admin), TwoFactor::TRUST_DAYS * 24 * 60, null, null, $request->isSecure(), true, false, 'Lax'));
        }

        return $response;
    }

    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('bo_2fa_pending');

        return is_array($pending) && ($pending['expires'] ?? 0) >= time() ? $pending : null;
    }

    private function completeLogin(Request $request, BackofficeAdmin $admin, bool $remember): RedirectResponse
    {
        Auth::guard('backoffice')->login($admin, $remember);
        $request->session()->regenerate();

        $admin->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        BackofficeAudit::log('login', 'Login backoffice' . (TwoFactor::mustChallenge($admin) ? ' (verifikasi 2 langkah)' : ''), admin: $admin);

        return redirect()->intended(route('backoffice.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        BackofficeAudit::log('logout', 'Logout backoffice');

        Auth::guard('backoffice')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('backoffice.login')->with('status', 'Anda sudah keluar dari backoffice.');
    }

    public function editPassword(): View
    {
        return view('backoffice.auth.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = Auth::guard('backoffice')->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (!Hash::check($request->input('current_password'), $admin->password)) {
            throw ValidationException::withMessages(['current_password' => 'Password saat ini salah.']);
        }

        $admin->forceFill(['password' => $request->input('password')])->save();
        BackofficeAudit::log('password_changed', 'Mengganti password backoffice');

        return back()->with('success', 'Password berhasil diganti.');
    }
}
