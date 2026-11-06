<?php

namespace Database\Seeders;

use App\Models\BackofficeAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun backoffice pertama untuk pegawai Vasetra.
 *
 * Isi di .env:
 *   BACKOFFICE_ADMIN_NAME="Admin Vasetra"
 *   BACKOFFICE_ADMIN_EMAIL=admin@vasetra.com
 *   BACKOFFICE_ADMIN_PASSWORD=          (kosongkan → dibuatkan acak & ditampilkan sekali)
 *
 * Jalankan: php artisan db:seed --class=BackofficeAdminSeeder
 * Aman dijalankan ulang: akun yang sudah ada tidak diubah.
 */
class BackofficeAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('backoffice.seed.email')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->warn('BACKOFFICE_ADMIN_EMAIL belum diisi di .env — akun backoffice tidak dibuat.');

            return;
        }

        if (BackofficeAdmin::query()->where('email', $email)->exists()) {
            $this->command?->info("Akun backoffice {$email} sudah ada — tidak diubah.");

            return;
        }

        $password = (string) (config('backoffice.seed.password') ?: Str::password(14, symbols: false));

        BackofficeAdmin::create([
            'name' => config('backoffice.seed.name') ?: 'Admin Vasetra',
            'email' => $email,
            'password' => $password,
            'role' => BackofficeAdmin::ROLE_SUPER,
            'is_active' => true,
        ]);

        $this->command?->info("Akun backoffice (Super Admin) dibuat: {$email}");
        $this->command?->line('Pegawai lain ditambahkan dari menu Pegawai di backoffice.');

        if (!config('backoffice.seed.password')) {
            $this->command?->warn("Password: {$password}  (simpan sekarang, tidak ditampilkan lagi)");
        }

        $this->command?->line('Login di: ' . url(config('backoffice.path') . '/login'));
    }
}
