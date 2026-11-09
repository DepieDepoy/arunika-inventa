<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Teks notifikasi dua bahasa (Indonesia / Inggris).
 *
 * Teks disimpan sebagai KUNCI (kalimat bahasa Indonesia) + parameter, lalu
 * diterjemahkan saat ditampilkan memakai lang/en.json. Bila kunci belum ada
 * di en.json, kalimat Indonesianya yang tampil (tidak pernah error).
 *
 * Bahasa penerima diambil dari kolom users.locale, yang diisi otomatis dari
 * header X-App-Locale aplikasi mobile (lihat middleware RememberAppLocale).
 * Dasbor web selalu menampilkan bahasa Indonesia.
 *
 * Jenis parameter:
 *   'name' => 'Budi'                    teks apa adanya
 *   'type' => ['t' => 'perbaikan']      ikut diterjemahkan
 *   'date' => ['date' => '2026-11-09']  tanggal, format mengikuti bahasa
 *
 * MENAMBAH NOTIFIKASI BARU:
 *   new GlobalNotification(
 *       type: 'contoh',
 *       title: NotificationText::make('Aset :asset dipinjam', ['asset' => $label]),
 *       message: NotificationText::make('...'),
 *   );
 *   lalu tambahkan terjemahannya di lang/en.json:
 *   "Aset :asset dipinjam": "Asset :asset was borrowed"
 */
final class NotificationText
{
    public const LOCALES = ['id', 'en'];

    public const DEFAULT_LOCALE = 'id';

    /** Bentuk teks yang disimpan: kunci + parameter. */
    public static function make(string $key, array $params = []): array
    {
        return ['key' => $key, 'params' => $params];
    }

    /** Kode bahasa yang didukung; selain itu jadi bahasa Indonesia. */
    public static function locale(?string $locale): string
    {
        $code = strtolower(substr((string) $locale, 0, 2));

        return in_array($code, self::LOCALES, true) ? $code : self::DEFAULT_LOCALE;
    }

    /**
     * Ubah teks (string biasa atau hasil make()) menjadi kalimat jadi.
     */
    public static function render(mixed $text, ?string $locale = null): string
    {
        if (is_string($text)) {
            return $text;
        }

        if (!is_array($text) || !isset($text['key'])) {
            return '';
        }

        $locale = self::locale($locale);
        $params = [];

        foreach ((array) ($text['params'] ?? []) as $name => $value) {
            $params[$name] = self::param($value, $locale);
        }

        return (string) trans((string) $text['key'], $params, $locale);
    }

    /**
     * Ambil judul/pesan dari data notifikasi tersimpan.
     * Notifikasi baru punya data['i18n'] (bisa diterjemahkan);
     * notifikasi lama hanya punya teks jadi (ditampilkan apa adanya).
     */
    public static function fromData(array $data, string $field, ?string $locale, string $default = ''): string
    {
        if (isset($data['i18n'][$field])) {
            $text = self::render($data['i18n'][$field], $locale);

            if ($text !== '') {
                return $text;
            }
        }

        $value = $data[$field] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    protected static function param(mixed $value, string $locale): string
    {
        if (is_array($value) && isset($value['t'])) {
            return (string) trans((string) $value['t'], [], $locale);
        }

        if (is_array($value) && isset($value['date'])) {
            try {
                return Carbon::parse($value['date'])->locale($locale)->translatedFormat('d M Y');
            } catch (Throwable) {
                return (string) $value['date'];
            }
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
