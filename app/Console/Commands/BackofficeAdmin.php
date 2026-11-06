<?php

namespace App\Console\Commands;

use App\Models\BackofficeAdmin as BackofficeAdminModel;
use App\Services\Backoffice\BackofficeAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Kelola akun backoffice pegawai Vasetra (terpisah dari user pelanggan).
 *
 *   php artisan vasetra:backoffice-admin budi@vasetra.com --name="Budi"      (tambah / reset password)
 *   php artisan vasetra:backoffice-admin budi@vasetra.com --deactivate       (nonaktifkan)
 *   php artisan vasetra:backoffice-admin budi@vasetra.com --activate
 *   php artisan vasetra:backoffice-admin --list
 */
class BackofficeAdmin extends Command
{
    protected $signature = 'vasetra:backoffice-admin
        {email? : Email pegawai}
        {--name= : Nama (wajib untuk akun baru)}
        {--role=editor : super_admin | editor (untuk akun baru)}
        {--password= : Password baru (kosongkan untuk dibuatkan acak)}
        {--deactivate : Nonaktifkan akun}
        {--activate : Aktifkan kembali akun}
        {--list : Tampilkan semua akun backoffice}';

    protected $description = 'Tambah / reset password / nonaktifkan akun backoffice pegawai Vasetra';

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->table(
                ['ID', 'Nama', 'Email', 'Peran', 'Status', 'Login terakhir'],
                BackofficeAdminModel::query()->orderBy('id')->get()->map(fn ($a) => [
                    $a->id, $a->name, $a->email, $a->roleLabel(), $a->is_active ? 'aktif' : 'nonaktif', $a->last_login_at?->format('d M Y H:i') ?? '-',
                ])->all()
            );

            return self::SUCCESS;
        }

        $email = Str::lower(trim((string) $this->argument('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Isi email yang valid, mis. php artisan vasetra:backoffice-admin budi@vasetra.com --name="Budi"');

            return self::INVALID;
        }

        $admin = BackofficeAdminModel::query()->where('email', $email)->first();

        if ($this->option('deactivate') || $this->option('activate')) {
            if (!$admin) {
                $this->error("Akun {$email} tidak ditemukan.");

                return self::FAILURE;
            }

            $active = (bool) $this->option('activate');
            $admin->forceFill(['is_active' => $active])->save();
            BackofficeAudit::log($active ? 'admin_activated' : 'admin_deactivated', ($active ? 'Mengaktifkan' : 'Menonaktifkan') . " akun backoffice {$email} (console)");
            $this->info("Akun {$email} " . ($active ? 'diaktifkan.' : 'dinonaktifkan.'));

            return self::SUCCESS;
        }

        $password = (string) ($this->option('password') ?: Str::password(14, symbols: false));

        if (strlen($password) < 10) {
            $this->error('Password minimal 10 karakter.');

            return self::INVALID;
        }

        if (!$admin) {
            $name = trim((string) $this->option('name'));

            if ($name === '') {
                $this->error('Akun baru wajib diberi nama: --name="Nama Pegawai"');

                return self::INVALID;
            }

            $role = in_array($this->option('role'), array_keys(BackofficeAdminModel::ROLES), true) ? $this->option('role') : BackofficeAdminModel::ROLE_EDITOR;
            $admin = BackofficeAdminModel::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role, 'is_active' => true]);
            BackofficeAudit::log('admin_created', "Membuat akun backoffice {$email} (console)");
            $this->info("Akun backoffice dibuat untuk {$name} <{$email}>.");
        } else {
            $admin->forceFill(['password' => $password, 'is_active' => true] + ($this->option('name') ? ['name' => $this->option('name')] : []))->save();
            BackofficeAudit::log('admin_password_reset', "Reset password akun backoffice {$email} (console)");
            $this->info("Password akun {$email} direset.");
        }

        if (!$this->option('password')) {
            $this->warn("Password: {$password}");
            $this->line('Simpan password ini sekarang — tidak akan ditampilkan lagi. Minta pegawai menggantinya setelah login.');
        }

        $this->line('Login di: ' . url(config('backoffice.path') . '/login'));

        return self::SUCCESS;
    }
}
