<?php

namespace App\Services\Push;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Saklar notifikasi push dari Backoffice:
 *  - web_enabled    : notifikasi browser (Chrome/Edge/Firefox)
 *  - mobile_enabled : notifikasi aplikasi Android/iOS (Firebase)
 * Notifikasi di lonceng (database) tetap tersimpan walau push dimatikan.
 */
class PushSettings
{
    public const KEY = 'push';
    public const CACHE_KEY = 'push_settings_v1';

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

        return $stored + ['web_enabled' => true, 'mobile_enabled' => true];
    }

    public static function webEnabled(): bool
    {
        return (bool) self::all()['web_enabled'];
    }

    public static function mobileEnabled(): bool
    {
        return (bool) self::all()['mobile_enabled'];
    }

    public static function save(bool $web, bool $mobile, ?int $adminId = null): void
    {
        SiteContent::query()->updateOrCreate(
            ['key' => self::KEY, 'locale' => 'id'],
            ['value' => ['web_enabled' => $web, 'mobile_enabled' => $mobile], 'updated_by' => $adminId]
        );

        Cache::forget(self::CACHE_KEY);
    }
}
