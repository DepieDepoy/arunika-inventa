<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BackofficeAdmin;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Backoffice\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan keamanan backoffice (khusus Super Admin):
 * kebijakan verifikasi 2 langkah & reset 2 langkah pegawai (mis. HP hilang).
 */
class SecurityController extends Controller
{
    public function edit(): View
    {
        return view('backoffice.settings.security', [
            'policy' => TwoFactor::policy(),
            'policies' => TwoFactor::POLICIES,
            'staff' => BackofficeAdmin::query()->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['two_factor' => ['required', 'in:' . implode(',', array_keys(TwoFactor::POLICIES))]]);

        $old = TwoFactor::policy();
        TwoFactor::savePolicy($data['two_factor'], auth('backoffice')->id());

        BackofficeAudit::log('security.settings', 'Verifikasi 2 langkah: ' . TwoFactor::POLICIES[$data['two_factor']], ['two_factor' => $old], $data);

        $me = auth('backoffice')->user();
        if (TwoFactor::mustSetup($me)) {
            return redirect()->route('backoffice.two-factor.show')->with('warning', 'Kebijakan disimpan. Aktifkan verifikasi 2 langkah untuk akun Anda sekarang.');
        }

        return back()->with('success', 'Pengaturan keamanan disimpan.');
    }

    /** Reset 2 langkah pegawai lain (HP hilang / ganti HP). */
    public function reset(BackofficeAdmin $staff): RedirectResponse
    {
        abort_if($staff->id === auth('backoffice')->id(), 422, 'Gunakan halaman Keamanan akun untuk akun sendiri.');

        TwoFactor::disable($staff);
        BackofficeAudit::log('two_factor_reset', 'Mereset verifikasi 2 langkah ' . $staff->name . ' (' . $staff->email . ')');

        return back()->with('success', 'Verifikasi 2 langkah ' . $staff->name . ' direset. Ia akan diminta mengaktifkan ulang saat login' . (TwoFactor::requiredFor($staff) ? '.' : ' (bila diwajibkan).'));
    }
}
