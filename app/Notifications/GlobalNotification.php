<?php

namespace App\Notifications;

use App\Support\NotificationText;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi umum (lonceng web, daftar notifikasi aplikasi, push).
 *
 * $title & $message boleh berupa:
 *   - string biasa (satu bahasa, cara lama), atau
 *   - NotificationText::make('Kalimat :nama', [...]) → dua bahasa:
 *     push dikirim sesuai bahasa penerima (users.locale), dan aplikasi
 *     menampilkan sesuai bahasa yang sedang dipilih. Dasbor web: Indonesia.
 */
class GlobalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string|array $title,
        public string|array $message,
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
     * Isi push notification (browser & aplikasi mobile), dalam bahasa penerima.
     */
    public function toPush(object $notifiable): array
    {
        $locale = $notifiable->locale ?? null;

        return [
            'title' => NotificationText::render($this->title, $locale),
            'body' => NotificationText::render($this->message, $locale),
            'url' => str_starts_with($this->url, 'http') ? $this->url : url($this->url),
            // Browser: klik notifikasi -> tandai dibaca lalu buka halaman tujuan.
            'open_url' => $this->id ? route('dashboard.notifications.read', $this->id) : null,
            'type' => $this->type,
            'data' => array_filter($this->extra, fn ($v) => is_scalar($v)),
        ];
    }

    /**
     * Data yang ditampilkan oleh lonceng navbar & daftar notifikasi aplikasi.
     *
     * title/message disimpan dalam bahasa Indonesia (dipakai dasbor web &
     * sebagai cadangan); i18n menyimpan kunci + parameter untuk diterjemahkan
     * saat dibaca (lihat NotificationText::fromData).
     */
    public function toDatabase(object $notifiable): array
    {
        $i18n = array_filter([
            'title' => is_array($this->title) ? $this->title : null,
            'message' => is_array($this->message) ? $this->message : null,
        ]);

        return array_merge([
            'type' => $this->type,
            'title' => NotificationText::render($this->title, NotificationText::DEFAULT_LOCALE),
            'message' => NotificationText::render($this->message, NotificationText::DEFAULT_LOCALE),
            'url' => $this->url,
        ], $i18n ? ['i18n' => $i18n] : [], $this->extra);
    }
}
