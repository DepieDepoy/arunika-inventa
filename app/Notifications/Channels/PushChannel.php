<?php

namespace App\Notifications\Channels;

use App\Services\Push\PushDispatcher;
use Illuminate\Notifications\Notification;

/**
 * Channel notifikasi Laravel: kirim juga sebagai push (browser & aplikasi mobile).
 * Notifikasi cukup punya method toPush($notifiable): array{title, body, url, type}.
 */
class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toPush') || empty($notifiable->id)) {
            return;
        }

        $payload = $notification->toPush($notifiable);
        if (!$payload) {
            return;
        }

        $payload['id'] = $notification->id ?? null;

        PushDispatcher::toUser((int) $notifiable->id, $payload);
    }
}
