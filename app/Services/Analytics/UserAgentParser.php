<?php

namespace App\Services\Analytics;

/**
 * Pembaca User-Agent sederhana (tanpa library): jenis perangkat, OS, browser, bot.
 */
class UserAgentParser
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|scrap|fetch|preview|monitor|uptime|lighthouse|pagespeed|headless|phantom|puppeteer|playwright|selenium|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|guzzle|postman|insomnia|httpclient|facebookexternalhit|whatsapp\/|telegrambot|discordbot|embedly|bingpreview|semrush|ahrefs|mj12|dotbot|petalbot|yandex|baidu/i';

    public static function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return $ua === '' || strlen($ua) < 20 || preg_match(self::BOT_PATTERN, $ua) === 1;
    }

    /**
     * @return array{device: string, os: string, browser: string}
     */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;

        return [
            'device' => self::device($ua),
            'os' => self::os($ua),
            'browser' => self::browser($ua),
        ];
    }

    public static function device(string $ua): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }

        // iPadOS modern mengaku "Macintosh" namun punya layar sentuh; tidak bisa dibedakan dari UA saja.
        if (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function os(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/windows phone/i', $ua) => 'Windows Phone',
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/cros/i', $ua) => 'ChromeOS',
            (bool) preg_match('/mac os x|macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Lainnya',
        };
    }

    public static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/instagram/i', $ua) => 'Instagram (in-app)',
            (bool) preg_match('/FBAN|FBAV|FB_IAB/', $ua) => 'Facebook (in-app)',
            (bool) preg_match('/musical_ly|tiktok|bytedance/i', $ua) => 'TikTok (in-app)',
            (bool) preg_match('/\bLine\//', $ua) => 'LINE (in-app)',
            (bool) preg_match('/edg(e|a|ios)?\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/ucbrowser/i', $ua) => 'UC Browser',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/chrome|crios|chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Lainnya',
        };
    }
}
