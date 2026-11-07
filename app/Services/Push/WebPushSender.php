<?php

namespace App\Services\Push;

use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Kirim notifikasi browser (Web Push / VAPID) ke semua browser milik user.
 */
class WebPushSender
{
    public static function configured(): bool
    {
        return (bool) config('push.webpush.public_key') && (bool) config('push.webpush.private_key');
    }

    /** Sudah diatur DAN dinyalakan dari Backoffice. */
    public static function active(): bool
    {
        return self::configured() && PushSettings::webEnabled();
    }

    /**
     * @return int jumlah browser yang berhasil dikirimi
     */
    public function send(int $userId, array $payload): int
    {
        if (!self::configured()) {
            return 0;
        }

        $subs = WebPushSubscription::query()->where('user_id', $userId)->get();
        if ($subs->isEmpty()) {
            return 0;
        }

        try {
            $push = new WebPush([
                'VAPID' => [
                    'subject' => config('push.webpush.subject'),
                    'publicKey' => config('push.webpush.public_key'),
                    'privateKey' => config('push.webpush.private_key'),
                ],
            ], ['TTL' => 3600 * 24, 'urgency' => 'high']);

            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subs as $sub) {
                $push->queueNotification(Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
                ]), $body);
            }

            $sent = 0;
            foreach ($push->flush() as $report) {
                $hash = WebPushSubscription::hashEndpoint($report->getEndpoint());

                if ($report->isSuccess()) {
                    $sent++;
                    WebPushSubscription::query()->where('endpoint_hash', $hash)->update(['last_used_at' => now()]);
                } elseif ($report->isSubscriptionExpired()) {
                    // Browser mencabut izin / langganan kedaluwarsa -> hapus.
                    WebPushSubscription::query()->where('endpoint_hash', $hash)->delete();
                } else {
                    Log::info('Web push gagal.', ['reason' => $report->getReason()]);
                }
            }

            return $sent;
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Unable to create the key')) {
                // Windows/XAMPP: OpenSSL butuh environment OPENSSL_CONF yang menunjuk ke openssl.cnf
                // (mis. D:\xampp84\php\extras\ssl\openssl.cnf), diset sebelum PHP / server dijalankan.
                Log::warning('Web push gagal: OpenSSL belum dikonfigurasi (set environment OPENSSL_CONF).');
            } else {
                report($e);
            }

            return 0;
        }
    }
}
