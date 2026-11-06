<?php

namespace App\Services\Analytics;

/**
 * Menentukan asal kunjungan dari utm_source atau Referer.
 */
class TrafficSource
{
    public const DIRECT = 'Langsung';
    public const INTERNAL = 'Internal';

    private const KNOWN = [
        'google' => 'Google',
        'bing' => 'Bing',
        'yahoo' => 'Yahoo',
        'duckduckgo' => 'DuckDuckGo',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'fb' => 'Facebook',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'lnkd' => 'LinkedIn',
        'whatsapp' => 'WhatsApp',
        'wa' => 'WhatsApp',
        'youtube' => 'YouTube',
        'youtu' => 'YouTube',
        'twitter' => 'X / Twitter',
        'telegram' => 'Telegram',
        'chatgpt' => 'ChatGPT',
        'openai' => 'ChatGPT',
        'claude' => 'Claude',
        'perplexity' => 'Perplexity',
    ];

    /**
     * @return array{source: string, referrer_host: ?string}
     */
    public static function resolve(?string $referer, ?string $utmSource, ?string $ownHost): array
    {
        $host = $referer ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www|m|l|lm|mobile)\./', '', $host) ?: '';

        if ($host !== '' && $ownHost && $host === preg_replace('/^www\./', '', strtolower($ownHost))) {
            return ['source' => self::INTERNAL, 'referrer_host' => $host];
        }

        $utm = trim((string) $utmSource);
        if ($utm !== '') {
            return ['source' => self::label($utm) ?? mb_substr(ucfirst($utm), 0, 60), 'referrer_host' => $host ?: null];
        }

        if ($host === '') {
            return ['source' => self::DIRECT, 'referrer_host' => null];
        }

        return ['source' => self::label($host) ?? mb_substr($host, 0, 60), 'referrer_host' => mb_substr($host, 0, 191)];
    }

    /**
     * "google.co.id" / "l.instagram.com" / "ig" -> label yang dikenal.
     */
    private static function label(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === 'ig') {
            return 'Instagram';
        }

        if (isset(self::KNOWN[$value])) {
            return self::KNOWN[$value];
        }

        foreach (explode('.', $value) as $part) {
            if ($part !== '' && strlen($part) > 1 && isset(self::KNOWN[$part])) {
                return self::KNOWN[$part];
            }
        }

        // Domain pendek: t.co (X), wa.me (WhatsApp), t.me (Telegram).
        return match ($value) {
            't.co', 'x.com' => 'X / Twitter',
            'wa.me' => 'WhatsApp',
            't.me' => 'Telegram',
            default => null,
        };
    }
}
