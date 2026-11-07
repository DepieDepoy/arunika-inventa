<?php

namespace App\Services\Push;

use Throwable;

/**
 * Pintu tunggal pengiriman push (browser + mobile).
 *
 * Pengiriman dijalankan SETELAH respons dikirim ke pengguna (afterResponse),
 * jadi halaman tidak menunggu server Google / browser vendor, dan tidak perlu
 * queue worker.
 */
class PushDispatcher
{
    /** @var array<int, array{0:int,1:array}> antrean pengiriman (untuk pengujian) */
    public static array $sent = [];

    public static function enabled(): bool
    {
        return (bool) config('push.enabled') && (WebPushSender::active() || FcmSender::active());
    }

    /**
     * @param array{title:string, body:string, url?:string, type?:string, id?:string, data?:array} $payload
     */
    public static function toUser(int $userId, array $payload): void
    {
        if (!self::enabled()) {
            return;
        }

        $payload += ['url' => '/dashboard', 'type' => 'general', 'icon' => asset('assets/images/brand/vasetra-mark.png')];
        $payload['title'] = mb_substr(strip_tags((string) $payload['title']), 0, 120);
        $payload['body'] = mb_substr(strip_tags((string) $payload['body']), 0, 300);
        self::$sent[] = [$userId, $payload];

        $job = static function () use ($userId, $payload) {
            try {
                if (WebPushSender::active()) {
                    app(WebPushSender::class)->send($userId, $payload);
                }
                if (FcmSender::active()) {
                    app(FcmSender::class)->send($userId, $payload);
                }
            } catch (Throwable $e) {
                report($e);
            }
        };

        app()->runningInConsole() && !app()->runningUnitTests()
            ? $job()
            : dispatch($job)->afterResponse();
    }
}
