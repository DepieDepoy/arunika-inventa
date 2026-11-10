<?php

namespace App\Services\Asset;

use App\Support\BranchAccess;
use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache angka ringkasan aset (dashboard, daftar aset, total paginasi).
 *
 * Hitungan atas ratusan ribu - jutaan aset tetap butuh waktu walau memakai
 * index, jadi hasilnya disimpan sementara. Kunci cache memakai "versi data"
 * per perusahaan yang naik setiap ada aset ditambah / diubah / dihapus
 * (lihat Asset::booted), sehingga angka langsung segar setelah ada perubahan
 * tanpa perlu menghapus cache satu per satu.
 */
class AssetStats
{
    public const TTL_SECONDS = 600;

    public static function version(int $companyId): int
    {
        try {
            return (int) Cache::get(self::versionKey($companyId), 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Tandai data aset perusahaan berubah -> semua angka ringkasan dihitung ulang.
     */
    public static function bump(?int $companyId): void
    {
        if (!$companyId) {
            return;
        }

        try {
            Cache::forever(self::versionKey($companyId), self::version($companyId) + 1);
        } catch (Throwable) {
            // Cache tidak tersedia: angka tetap dihitung langsung.
        }
    }

    /**
     * Ambil dari cache (per perusahaan + cakupan cabang user) atau hitung.
     */
    public static function remember(int $companyId, string $name, Closure $compute, ?int $ttl = null): mixed
    {
        $ids = BranchAccess::accessibleBranchIds();
        $scope = $ids === null ? 'all' : implode('-', $ids);
        $key = sprintf('asset-stats:%d:v%d:%s:%s', $companyId, self::version($companyId), $scope, $name);

        try {
            return Cache::remember($key, $ttl ?? self::TTL_SECONDS, $compute);
        } catch (Throwable) {
            return $compute();
        }
    }

    private static function versionKey(int $companyId): string
    {
        return 'asset-stats-version:' . $companyId;
    }
}
