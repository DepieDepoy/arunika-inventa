<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Bersihkan pendaftaran palsu dari bot (nama berisi link / huruf non-Latin).
 *
 * Perusahaan dianggap spam hanya bila SEMUA terpenuhi:
 *  - tidak ada user yang pernah verifikasi email atau login,
 *  - tidak ada transaksi pembayaran,
 *  - nama perusahaan / nama user berisi link atau huruf non-Latin.
 *
 * Bawaan: hanya menampilkan daftar. Gunakan --force untuk menghapus.
 */
class PurgeSpamSignups extends Command
{
    protected $signature = 'security:purge-spam-signups
        {--days=60 : Hanya perusahaan yang dibuat N hari terakhir}
        {--force : Benar-benar menghapus (tanpa ini hanya menampilkan)}';

    protected $description = 'Tampilkan / hapus pendaftaran perusahaan palsu hasil bot spam';

    /** Link, atau huruf selain Latin (mis. Kiril). */
    protected const SPAM_PATTERN = '/(https?:|www\.|:\/\/)|[^\p{Latin}\p{M}\p{N}\p{P}\p{S}\p{Z}\s]/iu';

    public function handle(): int
    {
        $since = now()->subDays(max(1, (int) $this->option('days')));

        $ids = [];

        DB::table('companies')
            ->where('created_at', '>=', $since)
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('users')->whereColumn('users.company_id', 'companies.id')
                ->where(fn ($w) => $w->whereNotNull('users.email_verified_at')->orWhereNotNull('users.last_login_at')))
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('payment_transactions')->whereColumn('payment_transactions.company_id', 'companies.id'))
            ->orderBy('id')
            ->select(['id', 'company_name', 'company_code', 'created_at'])
            ->chunkById(500, function ($companies) use (&$ids) {
                $users = DB::table('users')
                    ->whereIn('company_id', $companies->pluck('id'))
                    ->get(['company_id', 'name', 'email'])
                    ->groupBy('company_id');

                foreach ($companies as $c) {
                    $names = collect([$c->company_name])->merge(($users[$c->id] ?? collect())->pluck('name'));

                    if ($names->contains(fn ($n) => preg_match(self::SPAM_PATTERN, (string) $n))) {
                        $ids[$c->id] = [
                            $c->id,
                            mb_strimwidth((string) $c->company_name, 0, 45, '…'),
                            optional(($users[$c->id] ?? collect())->first())->email,
                            $c->created_at,
                        ];
                    }
                }
            });

        $total = count($ids);
        $this->info("Perusahaan spam terdeteksi: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $this->table(['ID', 'Nama perusahaan', 'Email', 'Dibuat'], array_slice(array_values($ids), 0, 15));

        if ($total > 15) {
            $this->line('… dan ' . ($total - 15) . ' lainnya.');
        }

        if (!$this->option('force')) {
            $this->warn('Mode tampil saja. Jalankan dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach (array_chunk(array_keys($ids), 200) as $chunk) {
            $codes = DB::table('companies')->whereIn('id', $chunk)->pluck('company_code');
            $userIds = DB::table('users')->whereIn('company_id', $chunk)->pluck('id');

            try {
                DB::transaction(function () use ($chunk, $userIds) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', \App\Models\User::class)
                        ->whereIn('tokenable_id', $userIds)
                        ->delete();
                    DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                    DB::table('notifications')
                        ->where('notifiable_type', \App\Models\User::class)
                        ->whereIn('notifiable_id', $userIds)
                        ->delete();
                    DB::table('analytics_hits')->whereIn('company_id', $chunk)->delete();
                    // subscriptions -> RESTRICT; sisanya terhapus lewat cascade companies.
                    DB::table('subscriptions')->whereIn('company_id', $chunk)->delete();
                    DB::table('companies')->whereIn('id', $chunk)->delete();
                });
            } catch (Throwable $e) {
                $this->error('Gagal menghapus sebagian data: ' . $e->getMessage());

                continue;
            }

            foreach ($codes as $code) {
                if ($code && preg_match('/\A[a-zA-Z0-9_-]+\z/', (string) $code)) {
                    File::deleteDirectory(storage_path('app/public/documents/' . $code));
                }
            }

            $deleted += count($chunk);
            $this->line("Terhapus {$deleted} / {$total}");
        }

        $this->info("Selesai. {$deleted} perusahaan spam beserta user & foldernya dihapus.");

        return self::SUCCESS;
    }
}
