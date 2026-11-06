<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BackofficeAdmin;
use App\Services\Backoffice\BackofficeAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola akun pegawai backoffice (khusus Super Admin).
 *
 * Tidak ada pendaftaran terbuka: akun hanya dibuat dari sini (atau seeder/artisan).
 * Password awal dibuat acak dan ditampilkan SEKALI ke Super Admin.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $staff = BackofficeAdmin::query()->orderByDesc('is_active')->orderBy('name')->get();

        return view('backoffice.staff.index', [
            'staff' => $staff,
            'roles' => BackofficeAdmin::ROLES,
            'me' => auth('backoffice')->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('backoffice_admins', 'email')],
            'role' => ['required', Rule::in(array_keys(BackofficeAdmin::ROLES))],
        ], [
            'email.unique' => 'Email ini sudah terdaftar sebagai pegawai backoffice.',
        ]);

        $password = Str::password(14, symbols: false);

        $admin = BackofficeAdmin::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'password' => $password,
            'is_active' => true,
        ]);

        BackofficeAudit::log('admin_created', "Menambah pegawai {$admin->email} sebagai {$admin->roleLabel()}");

        return redirect()->route('backoffice.staff.index')->with('new_password', [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => $password,
        ]);
    }

    public function update(Request $request, BackofficeAdmin $staff): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::in(array_keys(BackofficeAdmin::ROLES))],
            'is_active' => ['required', 'boolean'],
        ]);

        $me = auth('backoffice')->user();

        if ($staff->is($me) && ($data['role'] !== BackofficeAdmin::ROLE_SUPER || !$data['is_active'])) {
            return back()->with('error', 'Anda tidak dapat menurunkan peran atau menonaktifkan akun sendiri.');
        }

        $losingSuper = $staff->isSuperAdmin() && $staff->is_active
            && ($data['role'] !== BackofficeAdmin::ROLE_SUPER || !$data['is_active']);

        if ($losingSuper && BackofficeAdmin::query()->where('role', BackofficeAdmin::ROLE_SUPER)->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Harus ada minimal satu Super Admin yang aktif.');
        }

        $before = $staff->only(['name', 'role', 'is_active']);
        $staff->fill($data)->save();

        if ($staff->wasChanged()) {
            BackofficeAudit::log('admin_updated', "Mengubah akun pegawai {$staff->email}", $before, $staff->only(['name', 'role', 'is_active']));
        }

        return back()->with('success', "Akun {$staff->name} diperbarui.");
    }

    public function resetPassword(BackofficeAdmin $staff): RedirectResponse
    {
        if ($staff->is(auth('backoffice')->user())) {
            return back()->with('error', 'Untuk akun sendiri, gunakan menu Ganti password.');
        }

        $password = Str::password(14, symbols: false);
        $staff->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        BackofficeAudit::log('admin_password_reset', "Reset password pegawai {$staff->email}");

        return redirect()->route('backoffice.staff.index')->with('new_password', [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => $password,
        ]);
    }
}
