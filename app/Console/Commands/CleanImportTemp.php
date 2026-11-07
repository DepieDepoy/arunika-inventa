<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Bersihkan file sementara import/export yang tertinggal:
 *  - file upload import (aset & user) yang dipratinjau tapi tidak dikonfirmasi,
 *  - file sementara Laravel Excel dari proses yang terputus.
 * File yang sudah diproses queue dihapus langsung oleh job-nya; perintah ini
 * hanya jaring pengaman agar folder tidak menumpuk.
 */
class CleanImportTemp extends Command
{
    /** Folder upload import di disk default (storage/app/private). */
    public const IMPORT_DIRS = ['temp/asset-import', 'temp/user-import'];

    protected $signature = 'imports:clean-temp {--hours=2 : Umur minimal file (jam) sebelum dihapus}';

    protected $description = 'Hapus file upload import & file sementara Excel yang tertinggal';

    public function handle(): int
    {
        // Minimal 1 jam: memberi waktu antrean memproses file yang baru dikonfirmasi.
        $hours = max(1, (int) $this->option('hours'));
        $limit = now()->subHours($hours)->getTimestamp();
        $deleted = 0;

        foreach (self::IMPORT_DIRS as $dir) {
            foreach (Storage::files($dir) as $path) {
                if (Storage::lastModified($path) < $limit && Storage::delete($path)) {
                    $deleted++;
                }
            }
        }

        $excelTemp = config('excel.temporary_files.local_path', storage_path('framework/cache/laravel-excel'));

        if (is_dir($excelTemp)) {
            foreach (File::files($excelTemp) as $file) {
                if ($file->getMTime() < $limit && @unlink($file->getPathname())) {
                    $deleted++;
                }
            }
        }

        $this->info("File sementara dihapus: {$deleted} (lebih dari {$hours} jam).");

        return self::SUCCESS;
    }
}
