<?php

namespace App\Services\Intelligence;

use App\Models\Company;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan Tera AI dari Backoffice:
 *  - enabled : aktif / nonaktif untuk semua pelanggan
 *  - mode    : basic (gratis, tanpa layanan luar) | gemini (Google Gemini, opsional)
 *  - kunci & model Gemini (kunci disimpan terenkripsi)
 * Per perusahaan bisa dimatikan lewat companies.ai_enabled.
 */
class TeraSettings
{
    public const KEY = 'tera_ai';
    public const CACHE_KEY = 'tera_ai_settings_v1';
    public const MODES = ['basic' => 'Dasar (gratis, tanpa layanan luar)', 'gemini' => 'Google Gemini (opsional)'];
    public const DEFAULT_GEMINI_MODEL = 'gemini-2.5-flash';

    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
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

        return $stored + [
            'enabled' => true,
            'mode' => 'basic',
            'gemini_model' => self::DEFAULT_GEMINI_MODEL,
            'gemini_key' => null,
        ];
    }

    public static function enabled(): bool
    {
        return (bool) self::all()['enabled'];
    }

    /** Aktif untuk perusahaan ini? (global & per perusahaan) */
    public static function enabledFor(?Company $company): bool
    {
        return self::enabled() && (!$company || (bool) ($company->ai_enabled ?? true));
    }

    public static function mode(): string
    {
        $mode = (string) self::all()['mode'];

        return $mode === 'gemini' && self::geminiKey() ? 'gemini' : 'basic';
    }

    public static function geminiKey(): ?string
    {
        $enc = self::all()['gemini_key'] ?? null;
        if (!$enc) {
            return null;
        }

        try {
            return Crypt::decryptString($enc);
        } catch (Throwable) {
            return null;
        }
    }

    public static function geminiModel(): string
    {
        $model = trim((string) (self::all()['gemini_model'] ?? ''));

        return preg_match('/^[a-z0-9.\-]{3,60}$/', $model) ? $model : self::DEFAULT_GEMINI_MODEL;
    }

    /**
     * @param array{enabled?:bool, mode?:string, gemini_model?:string, gemini_key?:?string, clear_key?:bool} $values
     */
    public static function save(array $values, ?int $adminId = null): void
    {
        $current = self::all();

        $next = [
            'enabled' => (bool) ($values['enabled'] ?? $current['enabled']),
            'mode' => array_key_exists($values['mode'] ?? '', self::MODES) ? $values['mode'] : $current['mode'],
            'gemini_model' => $values['gemini_model'] ?? $current['gemini_model'],
            'gemini_key' => $current['gemini_key'],
        ];

        if (!empty($values['clear_key'])) {
            $next['gemini_key'] = null;
        } elseif (!empty($values['gemini_key'])) {
            $next['gemini_key'] = Crypt::encryptString(trim($values['gemini_key']));
        }

        SiteContent::query()->updateOrCreate(
            ['key' => self::KEY, 'locale' => 'id'],
            ['value' => $next, 'updated_by' => $adminId]
        );

        Cache::forget(self::CACHE_KEY);
    }
}
