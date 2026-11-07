<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Buat pasangan kunci VAPID untuk notifikasi browser.
 *   php artisan vasetra:vapid-keys          -> tampilkan saja
 *   php artisan vasetra:vapid-keys --write  -> tulis ke .env bila belum ada
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'vasetra:vapid-keys {--write : Tulis ke file .env bila VAPID belum diisi}';

    protected $description = 'Buat kunci VAPID untuk notifikasi browser (Web Push)';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        if (!$this->option('write')) {
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            $this->comment('Salin ke .env. Jangan ganti kunci setelah produksi: semua langganan browser lama akan tidak berlaku.');

            return self::SUCCESS;
        }

        $path = base_path('.env');
        $env = is_file($path) ? (string) file_get_contents($path) : '';

        if (preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $env)) {
            $this->warn('VAPID sudah ada di .env, tidak diubah.');

            return self::SUCCESS;
        }

        $env = preg_replace('/^VAPID_(PUBLIC|PRIVATE)_KEY=.*\R?/m', '', $env);
        $env = rtrim($env) . PHP_EOL . PHP_EOL
            . '# Notifikasi browser (Web Push)' . PHP_EOL
            . 'VAPID_PUBLIC_KEY=' . $keys['publicKey'] . PHP_EOL
            . 'VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;

        file_put_contents($path, $env);
        $this->info('Kunci VAPID ditulis ke .env.');

        return self::SUCCESS;
    }
}
