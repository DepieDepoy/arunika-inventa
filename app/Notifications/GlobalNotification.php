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
     * Notifikasi ini disimpan ke database,
     * bukan dikirim melalui email.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
