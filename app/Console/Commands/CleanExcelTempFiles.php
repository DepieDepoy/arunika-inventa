<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class CleanExcelTempFiles extends Command
{
    protected $signature = 'excel:clean-temp
                            {--delete : Benar-benar menghapus file lama, bukan simulasi}';

    protected $description = 'Memeriksa dan membersihkan file sementara Laravel Excel yang berumur lebih dari 48 jam';

    public function handle(): int
    {
        $directory = storage_path('framework/cache/laravel-excel');

        if (!File::isDirectory($directory)) {
            $this->info('Folder temporary Laravel Excel belum ada.');
            return self::SUCCESS;
        }

        $cutoff = now()->subHours(48)->timestamp;

        $checked = 0;
        $candidates = 0;
        $deleted = 0;
        $failed = 0;

        $deleteMode = (bool) $this->option('delete');

        $this->info('Folder: ' . $directory);
        $this->info('Batas umur file: 48 jam');
        $this->info(
            'Mode: ' . ($deleteMode ? 'PENGHAPUSAN AKTIF' : 'SIMULASI')
        );

        foreach (File::allFiles($directory) as $file) {
            /** @var SplFileInfo $file */

            try {
                $checked++;

                $path = $file->getPathname();
                $modifiedAt = $file->getMTime();
                $ageHours = round(
                    (time() - $modifiedAt) / 3600,
                    2
                );

                // File yang masih berumur kurang dari 48 jam dilewati.
                if ($modifiedAt >= $cutoff) {
                    $this->line(
                        "[AMAN] {$ageHours} jam | {$path}"
                    );

                    continue;
                }

                $candidates++;

                // Default hanya simulasi, tidak menghapus file.
                if (!$deleteMode) {
                    $this->warn(
                        "[KANDIDAT HAPUS] {$ageHours} jam | {$path}"
                    );

                    continue;
                }

                // Hapus hanya file yang lolos batas umur 48 jam.
                if (File::delete($path)) {
                    $deleted++;

                    $this->info(
                        "[DIHAPUS] {$ageHours} jam | {$path}"
                    );
                } else {
                    $failed++;

                    $this->error(
                        "[GAGAL] {$path}"
                    );
                }
            } catch (Throwable $e) {
                $failed++;

                report($e);

                $this->error(
                    'Terjadi kesalahan saat memproses file: '
                    . $e->getMessage()
                );
            }
        }

        $this->newLine();
        $this->info("Total file diperiksa: {$checked}");
        $this->info("Kandidat file lama: {$candidates}");
        $this->info("File berhasil dihapus: {$deleted}");
        $this->info("File gagal diproses: {$failed}");

        if (!$deleteMode) {
            $this->warn(
                'MODE SIMULASI: tidak ada file yang dihapus.'
            );

            $this->info(
                'Periksa hasilnya terlebih dahulu sebelum mengaktifkan penghapusan.'
            );
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
