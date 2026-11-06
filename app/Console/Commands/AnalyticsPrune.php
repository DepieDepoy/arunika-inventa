<?php

namespace App\Console\Commands;

use App\Models\AnalyticsHit;
use Illuminate\Console\Command;

/**
 * Kosongkan IP yang melewati masa simpan & hapus data kunjungan yang sudah sangat lama.
 */
class AnalyticsPrune extends Command
{
    protected $signature = 'analytics:prune';

    protected $description = 'Hapus IP pengunjung yang melewati masa simpan dan data analitik lama';

    public function handle(): int
    {
        $ipDays = max(1, (int) config('analytics.ip_retention_days', 60));
        $keepDays = max($ipDays, (int) config('analytics.retention_days', 400));

        $masked = AnalyticsHit::query()
            ->whereNotNull('ip')
            ->where('created_at', '<', now()->subDays($ipDays))
            ->update(['ip' => null]);

        $deleted = AnalyticsHit::query()
            ->where('created_at', '<', now()->subDays($keepDays))
            ->delete();

        $this->info("IP dikosongkan: {$masked} baris (> {$ipDays} hari). Dihapus: {$deleted} baris (> {$keepDays} hari).");

        return self::SUCCESS;
    }
}
