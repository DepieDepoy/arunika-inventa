<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class CompanyStorage
{
    /**
     * Batas storage yang berlaku hari ini:
     * paket berlaku (upgrade menimpa regular) + add-on storage aktif.
     * Sumber tunggal: SubscriptionService::quota().
     */
    public static function limitBytes(int $companyId): int
    {
        return (int) app(\App\Services\Subscription\SubscriptionService::class)
            ->quota($companyId)['storage'];
    }

    /**
     * Pastikan upload baru masih muat dalam kuota storage.
     *
     * @param  array<int, UploadedFile|null>  $files
     * @return array{allowed:bool, used:int, limit:int, incoming:int, message:?string}
     */
    public static function checkUpload(int $companyId, array $files): array
    {
        $used = self::usedBytes($companyId);
        $limit = self::limitBytes($companyId);
        $incoming = self::uploadBytes($files);

        $allowed = $incoming <= 0 || ($used + $incoming) <= $limit;

        return [
            'allowed' => $allowed,
            'used' => $used,
            'limit' => $limit,
            'incoming' => $incoming,
            'message' => $allowed
                ? null
                : 'Kuota storage perusahaan tidak mencukupi. Terpakai '
                    . self::formatBytes($used) . ' dari ' . self::formatBytes($limit)
                    . ', file baru ' . self::formatBytes($incoming)
                    . '. Hapus file lama, tambah storage, atau upgrade paket.',
        ];
    }

    /**
     * Menghitung seluruh file fisik milik perusahaan,
     * sesuai folder yang dihitung oleh Usage & Limits.
     */
    public static function usedBytes(int $companyId): int
    {
        $company = Company::find($companyId);

        if (!$company || !$company->company_code) {
            return 0;
        }

        $directory = storage_path(
            'app/public/documents/' . $company->company_code
        );

        if (!File::isDirectory($directory)) {
            return 0;
        }

        $total = 0;

        foreach (File::allFiles($directory) as $file) {
            $size = $file->getSize();

            if ($size !== false) {
                $total += $size;
            }
        }

        return $total;
    }

    /**
     * Menjumlahkan ukuran file upload baru.
     */
    public static function uploadBytes(array $files): int
    {
        $total = 0;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $total += (int) $file->getSize();
            }
        }

        return $total;
    }

    /**
     * Menghitung ukuran file lama yang akan dihapus.
     * Path berasal dari database dan harus relatif terhadap
     * storage/app/public.
     */
    public static function deletedBytes(iterable $paths): int
    {
        $total = 0;
        $baseDirectory = realpath(storage_path('app/public'));

        if ($baseDirectory === false) {
            return 0;
        }

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $fullPath = realpath(
                storage_path('app/public/' . ltrim($path, '/\\'))
            );

            // Hanya menghitung file di dalam storage/app/public.
            if (
                $fullPath !== false
                && is_file($fullPath)
                && str_starts_with(
                    $fullPath,
                    $baseDirectory . DIRECTORY_SEPARATOR
                )
            ) {
                $size = filesize($fullPath);

                if ($size !== false) {
                    $total += $size;
                }
            }
        }

        return $total;
    }

    /**
     * Mengecek apakah perubahan storage masih dalam batas.
     */
    public static function canFit(
        int $companyId,
        int $incomingBytes = 0,
        int $deletedBytes = 0
    ): bool {
        $used = self::usedBytes($companyId);
        $limit = self::limitBytes($companyId);

        $projected = max(0, $used - $deletedBytes)
            + $incomingBytes;

        return $projected <= $limit;
    }

    public static function formatBytes(int $bytes): string
    {
        return number_format(
            $bytes / 1048576,
            2,
            ',',
            '.'
        ) . ' MB';
    }
}
