<?php

namespace App\Console\Commands;

use App\Services\Backoffice\CustomerDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memperbarui companies.storage_used_bytes (scan folder documents/{kode})
 * untuk urutan "Storage terbesar", total storage & peringatan storage di backoffice.
 */
class ScanCompanyStorage extends Command
{
    protected $signature = 'backoffice:scan-storage {--company= : ID perusahaan tertentu}';

    protected $description = 'Hitung ulang storage terpakai tiap perusahaan untuk backoffice';

    public function handle(CustomerDirectory $directory): int
    {
        $count = 0;

        DB::table('companies')
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', (int) $id))
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows) use ($directory, &$count) {
                foreach ($rows as $row) {
                    $directory->storageUsed((int) $row->id, true);
                    $count++;
                }
            });

        $this->info("Storage {$count} perusahaan diperbarui.");

        return self::SUCCESS;
    }
}
