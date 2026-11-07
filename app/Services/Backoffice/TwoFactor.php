<?php

namespace App\Services\Backoffice;

use App\Models\BackofficeAdmin;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Verifikasi 2 langkah backoffice dengan aplikasi authenticator
 * (Google Authenticator / Microsoft Authenticator / Authy) — TOTP RFC 6238:
 * kode 6 digit, berganti tiap 30 detik, bekerja offline (cukup jam HP & server benar).
 *
 * Kebijakan (diatur di Backoffice › Keamanan):
 *   off      : 2 langkah tidak dipakai sama sekali
 *   optional : pegawai boleh mengaktifkan sendiri
 *   super    : wajib untuk Super Admin, opsional untuk Editor
 *   all      : wajib untuk semua pegawai
 */
class TwoFactor
{
    public const POLICIES = [
        'off' => 'Nonaktif',
        'optional' => 'Opsional — pegawai boleh mengaktifkan sendiri',
        'super' => 'Wajib untuk Super Admin',
        'all' => 'Wajib untuk semua pegawai',
    ];

    public const KEY = 'backoffice_security';
    public const CACHE_KEY = 'backoffice_security_v1';
    public const TRUST_COOKIE = 'bo_2fa_trust';
    public const TRUST_DAYS = 30;
    public const ISSUER = 'Vasetra Backoffice';

    private const PERIOD = 30;
    private const DIGITS = 6;
    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /* ------------------------------------------------------------------ */
    /* Kebijakan                                                          */
    /* ------------------------------------------------------------------ */

    public static function policy(): string
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (!Schema::hasTable('site_contents')) {
                    return [];
                }
                $v = SiteContent::query()->where('key', self::KEY)->where('locale', 'id')->value('value');

                return is_array($v) ? $v : [];
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });

        $policy = (string) ($stored['two_factor'] ?? 'optional');

        return array_key_exists($policy, self::POLICIES) ? $policy : 'optional';
    }

    public static function savePolicy(string $policy, ?int $adminId = null): void
    {
        SiteContent::query()->updateOrCreate(
            ['key' => self::KEY, 'locale' => 'id'],
            ['value' => ['two_factor' => $policy], 'updated_by' => $adminId]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** Fitur 2 langkah dipakai? (off = diabaikan sepenuhnya, termasuk yang sudah aktif). */
    public static function inUse(): bool
    {
        return self::policy() !== 'off';
    }

    public static function requiredFor(BackofficeAdmin $admin): bool
    {
        return match (self::policy()) {
            'all' => true,
            'super' => $admin->isSuperAdmin(),
            default => false,
        };
    }

    public static function enabled(BackofficeAdmin $admin): bool
    {
        return $admin->two_factor_confirmed_at !== null && !empty($admin->two_factor_secret);
    }

    /** Login perlu memasukkan kode? */
    public static function mustChallenge(BackofficeAdmin $admin): bool
    {
        return self::inUse() && self::enabled($admin);
    }

    /** Harus menyiapkan 2 langkah dulu sebelum memakai backoffice? */
    public static function mustSetup(BackofficeAdmin $admin): bool
    {
        return self::requiredFor($admin) && !self::enabled($admin);
    }

    /* ------------------------------------------------------------------ */
    /* TOTP                                                               */
    /* ------------------------------------------------------------------ */

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function otpauthUrl(BackofficeAdmin $admin, string $secret): string
    {
        $label = rawurlencode(self::ISSUER . ':' . $admin->email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode(self::ISSUER) . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), self::PERIOD);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Cocokkan kode (toleransi ±1 periode = 30 detik untuk selisih jam).
     * Kode yang sudah dipakai tidak bisa dipakai ulang (cegah replay).
     */
    public static function verify(string $secret, string $code, ?int $adminId = null): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $now = time();
        foreach ([0, -1, 1] as $step) {
            $ts = $now + $step * self::PERIOD;
            if (hash_equals(self::code($secret, $ts), $code)) {
                if ($adminId) {
                    $key = "bo_2fa_used_{$adminId}_" . intdiv($ts, self::PERIOD);
                    if (!Cache::add($key, true, self::PERIOD * 3)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------ */
    /* Simpan / kode cadangan                                             */
    /* ------------------------------------------------------------------ */

    public static function secretOf(BackofficeAdmin $admin): ?string
    {
        try {
            return $admin->two_factor_secret ? Crypt::decryptString($admin->two_factor_secret) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return string[] kode cadangan (ditampilkan sekali) */
    public static function enable(BackofficeAdmin $admin, string $secret): array
    {
        $codes = self::newRecoveryCodes();
        $admin->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_map(fn ($c) => hash('sha256', $c), $codes))),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    /** @return string[] */
    public static function regenerateRecoveryCodes(BackofficeAdmin $admin): array
    {
        $codes = self::newRecoveryCodes();
        $admin->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_map(fn ($c) => hash('sha256', $c), $codes))),
        ])->save();

        return $codes;
    }

    public static function disable(BackofficeAdmin $admin): void
    {
        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /** Pakai satu kode cadangan (sekali pakai). */
    public static function useRecoveryCode(BackofficeAdmin $admin, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        if (strlen($code) !== 10) {
            return false;
        }

        $hashes = self::recoveryHashes($admin);
        $target = hash('sha256', substr($code, 0, 5) . '-' . substr($code, 5));
        $index = array_search($target, $hashes, true);
        if ($index === false) {
            return false;
        }

        unset($hashes[$index]);
        $admin->forceFill(['two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($hashes)))])->save();

        return true;
    }

    public static function recoveryCodesLeft(BackofficeAdmin $admin): int
    {
        return count(self::recoveryHashes($admin));
    }

    private static function recoveryHashes(BackofficeAdmin $admin): array
    {
        try {
            return $admin->two_factor_recovery_codes ? (array) json_decode(Crypt::decryptString($admin->two_factor_recovery_codes), true) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /** @return string[] mis. "K7Q2M-9XW4P" */
    private static function newRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }

        return $codes;
    }

    /* ------------------------------------------------------------------ */
    /* "Ingat perangkat ini 30 hari"                                       */
    /* ------------------------------------------------------------------ */

    /** Nilai cookie terikat ke admin & kunci rahasianya (reset 2FA = cookie lama tidak berlaku). */
    public static function trustValue(BackofficeAdmin $admin): string
    {
        $exp = now()->addDays(self::TRUST_DAYS)->timestamp;

        return $admin->id . '|' . $exp . '|' . hash_hmac('sha256', $admin->id . '|' . $exp . '|' . $admin->two_factor_secret, (string) config('app.key'));
    }

    public static function isTrusted(Request $request, BackofficeAdmin $admin): bool
    {
        $value = (string) $request->cookie(self::TRUST_COOKIE);
        $parts = explode('|', $value);
        if (count($parts) !== 3 || (int) $parts[0] !== (int) $admin->id || (int) $parts[1] < time()) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts[0] . '|' . $parts[1] . '|' . $admin->two_factor_secret, (string) config('app.key'));

        return hash_equals($expected, $parts[2]);
    }

    /* ------------------------------------------------------------------ */

    private static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::B32[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32) ?? '');
        $bits = '';
        foreach (str_split($b32) as $c) {
            $bits .= str_pad(decbin((int) strpos(self::B32, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    public static function maskEmail(string $email): string
    {
        return Str::mask($email, '*', 2, max(1, strpos($email, '@') - 3));
    }
}
