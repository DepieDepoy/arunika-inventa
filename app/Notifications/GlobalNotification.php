<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GlobalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public string $url = '/dashboard',
        public array $extra = [],
    ) {
    }

    /**
     * Disimpan ke database (lonceng navbar / aplikasi) dan dikirim sebagai
     * push ke browser & HP yang sudah mengizinkan notifikasi.
     */
    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\PushChannel::class];
    }

    /**
     * Isi push notification (browser & aplikasi mobile).
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'url' => str_starts_with($this->url, 'http') ? $this->url : url($this->url),
            // Browser: klik notifikasi -> tandai dibaca lalu buka halaman tujuan.
            'open_url' => $this->id ? route('dashboard.notifications.read', $this->id) : null,
            'type' => $this->type,
            'data' => array_filter($this->extra, fn ($v) => is_scalar($v)),
        ];
    }

    /**
     * Data yang ditampilkan oleh lonceng navbar.
     */
    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ], $this->extra);
    }
}
