<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Batas jumlah pendaftaran yang BERHASIL (PT dibuat + email verifikasi terkirim).
 *
 * Hanya pendaftaran sukses yang dihitung, sehingga salah isi form / email sudah
 * dipakai tidak mengunci pengguna. Bot yang berhasil lolos tetap dibatasi:
 *  - per IP   : 3 per jam, 10 per hari
 *  - global   : 20 per 10 menit, 100 per jam (semua IP digabung)
 */
class RegistrationQuota
{
    /** @return array<int, array{key: string, max: int, decay: int}> */
    protected static function limits(Request $request): array
    {
        $ip = (string) $request->ip();

        return [
            ['key' => 'register-ok-ip-hour:' . $ip, 'max' => 3, 'decay' => 3600],
            ['key' => 'register-ok-ip-day:' . $ip, 'max' => 10, 'decay' => 86400],
            ['key' => 'register-ok-global-10m', 'max' => 20, 'decay' => 600],
            ['key' => 'register-ok-global-hour', 'max' => 100, 'decay' => 3600],
        ];
    }

    /** Detik yang harus ditunggu bila kuota habis, atau null bila masih boleh. */
    public static function blockedFor(Request $request): ?int
    {
        $wait = null;

        foreach (self::limits($request) as $l) {
            if (RateLimiter::tooManyAttempts($l['key'], $l['max'])) {
                $wait = max($wait ?? 0, RateLimiter::availableIn($l['key']));
            }
        }

        return $wait;
    }

    /** Catat satu pendaftaran yang berhasil. */
    public static function hit(Request $request): void
    {
        foreach (self::limits($request) as $l) {
            RateLimiter::hit($l['key'], $l['decay']);
        }
    }

    /** Pesan untuk ditampilkan di form, dengan perkiraan waktu tunggu. */
    public static function message(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return $minutes >= 60
            ? 'Too many registrations from your network. Please try again in about ' . (int) ceil($minutes / 60) . ' hour(s).'
            : 'Too many registrations from your network. Please try again in about ' . $minutes . ' minute(s).';
    }
}
