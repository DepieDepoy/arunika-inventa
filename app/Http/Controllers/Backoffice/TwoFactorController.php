<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Backoffice\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Verifikasi 2 langkah milik pegawai yang sedang login: aktifkan, nonaktifkan, kode cadangan.
 */
class TwoFactorController extends Controller
{
    public function show(Request $request): View
    {
        $admin = Auth::guard('backoffice')->user();
        $enabled = TwoFactor::enabled($admin);

        // Kunci baru disimpan di sesi sampai dikonfirmasi dengan kode yang benar.
        $secret = null;
        if (!$enabled) {
            $secret = $request->session()->get('bo_2fa_setup') ?: TwoFactor::generateSecret();
            $request->session()->put('bo_2fa_setup', $secret);
        }

        return view('backoffice.auth.two-factor', [
            'admin' => $admin,
            'enabled' => $enabled,
            'policy' => TwoFactor::policy(),
            'required' => TwoFactor::requiredFor($admin),
            'secret' => $secret,
            'otpauth' => $secret ? TwoFactor::otpauthUrl($admin, $secret) : null,
            'codesLeft' => $enabled ? TwoFactor::recoveryCodesLeft($admin) : 0,
            'newCodes' => $request->session()->pull('bo_2fa_new_codes'),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $admin = Auth::guard('backoffice')->user();
        $secret = (string) $request->session()->get('bo_2fa_setup');

        $request->validate(['code' => ['required', 'string']], ['code.required' => 'Masukkan kode 6 digit dari aplikasi authenticator.']);

        if ($secret === '' || !TwoFactor::verify($secret, (string) $request->input('code'), $admin->id)) {
            return back()->withErrors(['code' => 'Kode tidak cocok. Pastikan jam di HP sudah otomatis, lalu masukkan kode terbaru.']);
        }

        $codes = TwoFactor::enable($admin, $secret);
        $request->session()->forget('bo_2fa_setup');
        $request->session()->put('bo_2fa_new_codes', $codes);

        BackofficeAudit::log('two_factor_enabled', 'Mengaktifkan verifikasi 2 langkah');

        return redirect()->route('backoffice.two-factor.show')->with('success', 'Verifikasi 2 langkah aktif. Simpan kode cadangan di bawah ini.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $admin = Auth::guard('backoffice')->user();

        if (TwoFactor::requiredFor($admin)) {
            return back()->withErrors(['password' => 'Verifikasi 2 langkah wajib untuk akun Anda dan tidak bisa dimatikan.']);
        }

        $request->validate(['password' => ['required', 'string']]);
        if (!Hash::check((string) $request->input('password'), $admin->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        TwoFactor::disable($admin);
        BackofficeAudit::log('two_factor_disabled', 'Menonaktifkan verifikasi 2 langkah');

        return redirect()->route('backoffice.two-factor.show')->with('success', 'Verifikasi 2 langkah dinonaktifkan.');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $admin = Auth::guard('backoffice')->user();

        $request->validate(['password' => ['required', 'string']]);
        if (!Hash::check((string) $request->input('password'), $admin->password) || !TwoFactor::enabled($admin)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $request->session()->put('bo_2fa_new_codes', TwoFactor::regenerateRecoveryCodes($admin));
        BackofficeAudit::log('two_factor_codes', 'Membuat ulang kode cadangan verifikasi 2 langkah');

        return redirect()->route('backoffice.two-factor.show')->with('success', 'Kode cadangan baru dibuat. Kode lama tidak berlaku lagi.');
    }
}
