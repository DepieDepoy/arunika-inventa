<?php

namespace App\Services\Analytics;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan analitik yang bisa diubah dari backoffice (mis. ID Google Analytics 4).
 * Disimpan di site_contents (key "analytics"), terpisah dari konten landing.
 */
class AnalyticsSettings
{
    public const KEY = 'analytics';
    public const CACHE_KEY = 'analytics_settings_v1';
    public const GA4_PATTERN = '/^G-[A-Z0-9]{4,16}$/';

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (!Schema::hasTable('site_contents')) {
                    return [];
                }

                $value = SiteContent::query()->where('key', self::KEY)->where('locale', 'id')->value('value');

                return is_array($value) ? $value : [];
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    public static function ga4Id(): ?string
    {
        $id = strtoupper(trim((string) (self::all()['ga4_id'] ?? '')));

        return preg_match(self::GA4_PATTERN, $id) ? $id : null;
    }

    public static function save(array $values, ?int $adminId = null): void
    {
        SiteContent::query()->updateOrCreate(
            ['key' => self::KEY, 'locale' => 'id'],
            ['value' => $values, 'updated_by' => $adminId],
        );

        Cache::forget(self::CACHE_KEY);
    }
}
