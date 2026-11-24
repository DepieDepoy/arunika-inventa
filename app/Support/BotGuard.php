<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Perlindungan form publik (daftar, lupa password, hapus akun) dari bot.
 *
 * 1. Honeypot : kolom tersembunyi "website" — manusia tidak melihatnya, bot mengisinya.
 * 2. Time-trap: waktu form dibuka (terenkripsi). Dikirim < 3 detik atau > 3 jam
 *    setelah dibuka = bukan manusia / form kedaluwarsa.
 * 3. Cloudflare Turnstile (captcha tanpa teka-teki): aktif otomatis bila
 *    TURNSTILE_SITE_KEY & TURNSTILE_SECRET_KEY diisi di .env.
 *
 * Batas jumlah request per IP / global diatur terpisah lewat RateLimiter
 * (lihat AppServiceProvider).
 */
class BotGuard
{
    public const HONEYPOT = 'website';
    public const TIMESTAMP = '_form_ts';
    public const MIN_SECONDS = 3;
    public const MAX_SECONDS = 10800;

    /**
     * Field tersembunyi untuk diletakkan di dalam <form>.
     * $withWidget = false bila widget Turnstile diletakkan terpisah lewat widget()
     * (mis. tepat di atas tombol kirim).
     */
    public static function fields(bool $withWidget = true): HtmlString
    {
        $ts = e(Crypt::encryptString((string) time()));
        $hp = self::HONEYPOT;
        $tsName = self::TIMESTAMP;

        $html = <<<HTML
<div aria-hidden="true" style="position:absolute!important;left:-10000px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;">
    <label for="{$hp}_field">Website</label>
    <input type="text" id="{$hp}_field" name="{$hp}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{$tsName}" value="{$ts}">
HTML;

        if ($withWidget) {
            $html .= self::widget();
        }

        return new HtmlString($html);
    }

    /** Widget Cloudflare Turnstile (kosong bila Turnstile tidak aktif). Selebar form. */
    public static function widget(): HtmlString
    {
        if (!self::turnstileEnabled()) {
            return new HtmlString('');
        }

        $siteKey = e((string) config('services.turnstile.site_key'));

        return new HtmlString(<<<HTML
<div class="cf-turnstile mb-3" data-sitekey="{$siteKey}" data-theme="light" data-size="flexible"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
HTML);
    }

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /**
     * Periksa request form. Mengembalikan pesan error (untuk ditampilkan) atau null bila lolos.
     */
    public static function check(Request $request, string $form): ?string
    {
        $reason = self::failure($request);

        if ($reason === null) {
            return null;
        }

        Log::warning('Form publik ditolak (perlindungan bot).', [
            'form' => $form,
            'reason' => $reason,
            'ip' => $request->ip(),
            'email' => substr((string) $request->input('email'), 0, 120),
            'user_agent' => substr((string) $request->userAgent(), 0, 200),
        ]);

        return $reason === 'expired'
            ? 'The form has expired. Please reload the page and try again.'
            : 'Verification failed. Please reload the page and try again.';
    }

    protected static function failure(Request $request): ?string
    {
        if (filled($request->input(self::HONEYPOT))) {
            return 'honeypot';
        }

        try {
            $openedAt = (int) Crypt::decryptString((string) $request->input(self::TIMESTAMP));
        } catch (DecryptException) {
            return 'timestamp_missing';
        }

        $elapsed = time() - $openedAt;

        if ($elapsed < self::MIN_SECONDS) {
            return 'too_fast';
        }

        if ($elapsed > self::MAX_SECONDS) {
            return 'expired';
        }

        if (self::turnstileEnabled() && !self::turnstilePasses($request)) {
            return 'turnstile';
        }

        return null;
    }

    protected static function turnstilePasses(Request $request): bool
    {
        $token = (string) $request->input('cf-turnstile-response');

        if ($token === '') {
            return false;
        }

        try {
            $res = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            return (bool) $res->json('success');
        } catch (Throwable $e) {
            // Tidak bisa menghubungi Cloudflare -> tolak (lebih aman daripada meloloskan bot).
            report($e);

            return false;
        }
    }
}
