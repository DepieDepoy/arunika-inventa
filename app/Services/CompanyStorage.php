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
     * Mengambil batas storage sesuai paket aktif.
     * Logikanya disamakan dengan halaman Usage & Limits.
     */
    
    public static function limitBytes(int $companyId): int
    {
        $subscription = \App\Models\Subscription::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        // Prioritas utama: total kuota yang tersimpan pada subscription.
        if (
            $subscription
            && (int) $subscription->total_storage_bytes > 0
        ) {
            return (int) $subscription->total_storage_bytes;
        }

        // Fallback untuk subscription lama yang belum memiliki total kuota.
        if ($subscription) {
            $baseStorageBytes = (int) (
                $subscription->base_storage_bytes ?? 0
            );

            $storageAddonBytes = (int) (
                $subscription->storage_addon_bytes ?? 0
            );

            $calculatedTotal = $baseStorageBytes + $storageAddonBytes;

            if ($calculatedTotal > 0) {
                return $calculatedTotal;
            }

            // Fallback terakhir menggunakan kuota dasar paket.
            $plan = \App\Models\Plan::find($subscription->plan_id);

            if ($plan && (int) $plan->max_storage_bytes > 0) {
                return (int) $plan->max_storage_bytes;
            }
        }

        // Default jika perusahaan belum memiliki subscription yang valid.
        return 536870912; // 512 MiB
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
