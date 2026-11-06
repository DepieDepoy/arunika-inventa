<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BackofficeAdmin;
use App\Services\Backoffice\BackofficeAudit;
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

        Auth::guard('backoffice')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        $admin->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        BackofficeAudit::log('login', 'Login backoffice', admin: $admin);

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
