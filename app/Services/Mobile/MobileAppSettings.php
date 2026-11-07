<?php

namespace App\Services\Mobile;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan aplikasi mobile dari Backoffice: versi minimal (paksa update),
 * versi terbaru, tautan toko, dan mode pemeliharaan. Bawaan dari config/mobile.php.
 */
class MobileAppSettings
{
    public const KEY = 'mobile_app';
    public const CACHE_KEY = 'mobile_app_settings_v1';
    public const PLATFORMS = ['android', 'ios'];

    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (!Schema::hasTable('site_contents')) {
                    return [];
                }
                $v = SiteContent::query()->where('key', self::KEY)->where('locale', 'id')->value('value');

                return is_array($v) ? $v : [];
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });

        $min = (string) config('mobile.min_app_version', '1.0.0');
        $latest = (string) config('mobile.latest_app_version', '1.0.0');

        return array_replace([
            'android_min' => $min,
            'android_latest' => $latest,
            'android_store_url' => config('mobile.store_url.android'),
            'ios_min' => $min,
            'ios_latest' => $latest,
            'ios_store_url' => config('mobile.store_url.ios'),
            'update_message' => 'Versi baru Vasetra sudah tersedia. Silakan perbarui aplikasi untuk melanjutkan.',
            'maintenance' => false,
            'maintenance_message' => 'Vasetra sedang dalam pemeliharaan sebentar. Silakan coba lagi beberapa saat lagi.',
        ], array_filter($stored, fn ($v) => $v !== null && $v !== ''));
    }

    public static function forPlatform(?string $platform): array
    {
        $s = self::all();
        $p = in_array($platform, self::PLATFORMS, true) ? $platform : 'android';

        return [
            'min_version' => $s["{$p}_min"],
            'latest_version' => $s["{$p}_latest"],
            'store_url' => $s["{$p}_store_url"],
        ];
    }

    /**
     * true bila $version lebih rendah dari versi minimal platform tsb.
     */
    public static function mustUpdate(?string $platform, ?string $version): bool
    {
        if (!$version || !preg_match('/^\d+(\.\d+){0,3}/', $version, $m)) {
            return false;
        }

        return version_compare($m[0], self::forPlatform($platform)['min_version'], '<');
    }

    public static function save(array $values, ?int $adminId = null): void
    {
        SiteContent::query()->updateOrCreate(
            ['key' => self::KEY, 'locale' => 'id'],
            ['value' => $values, 'updated_by' => $adminId]
        );

        Cache::forget(self::CACHE_KEY);
    }
}
