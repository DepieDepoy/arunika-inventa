<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Pembatas percobaan login (web & API).
 *
 * Layer 1 - per akun + IP:
 *   Setelah N kali gagal, akun dari IP tersebut dikunci X menit.
 *
 * Layer 2 - per IP:
 *   Mencegah satu IP mencoba banyak akun berbeda
 *   (misalnya menebak password default ke banyak user).
 *
 * Nilai diatur di config/auth.php -> login_throttle.
 */
class LoginThrottle
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIG
    |--------------------------------------------------------------------------
    */

    public function maxAttempts(): int
    {
        return max(1, (int) config('auth.login_throttle.max_attempts', 5));
    }

    public function decaySeconds(): int
    {
        return max(1, (int) config('auth.login_throttle.decay_minutes', 15)) * 60;
    }

    public function ipMaxAttempts(): int
    {
        return max(1, (int) config('auth.login_throttle.ip_max_attempts', 20));
    }

    /*
    |--------------------------------------------------------------------------
    | KEYS
    |--------------------------------------------------------------------------
    */

    protected function normalizedLogin(): string
    {
        return Str::lower(trim((string) $this->request->input('login')));
    }

    protected function accountKey(): string
    {
        return 'login-fail:account:'
            . sha1($this->normalizedLogin() . '|' . $this->request->ip());
    }

    protected function ipKey(): string
    {
        return 'login-fail:ip:' . sha1((string) $this->request->ip());
    }

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    /**
     * Sisa detik penguncian. 0 = tidak terkunci.
     */
    public function lockedSeconds(): int
    {
        $seconds = 0;

        if (RateLimiter::tooManyAttempts($this->accountKey(), $this->maxAttempts())) {
            $seconds = max($seconds, RateLimiter::availableIn($this->accountKey()));
        }

        if (RateLimiter::tooManyAttempts($this->ipKey(), $this->ipMaxAttempts())) {
            $seconds = max($seconds, RateLimiter::availableIn($this->ipKey()));
        }

        return $seconds;
    }

    public function isLocked(): bool
    {
        return $this->lockedSeconds() > 0;
    }

    /**
     * Catat percobaan gagal.
     */
    public function hit(): void
    {
        RateLimiter::hit($this->accountKey(), $this->decaySeconds());
        RateLimiter::hit($this->ipKey(), $this->decaySeconds());

        if ($this->isLocked()) {
            event(new Lockout($this->request));

            Log::warning('Login dikunci karena terlalu banyak percobaan gagal.', [
                'login' => $this->normalizedLogin(),
                'ip' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
            ]);
        }
    }

    /**
     * Sisa percobaan untuk akun ini sebelum dikunci.
     */
    public function remainingAttempts(): int
    {
        return max(
            0,
            RateLimiter::remaining($this->accountKey(), $this->maxAttempts())
        );
    }

    /**
     * Login berhasil: reset hitungan akun.
     * Hitungan per IP sengaja tidak di-reset agar
     * login sukses ke satu akun tidak membuka kunci
     * percobaan ke akun lain.
     */
    public function clear(): void
    {
        RateLimiter::clear($this->accountKey());
    }

    /**
     * Samakan waktu respons saat akun tidak ditemukan.
     *
     * Tanpa ini, respons "akun tidak ada" jauh lebih cepat
     * daripada "password salah" (karena bcrypt), sehingga
     * penyerang bisa menebak email/no. HP yang terdaftar.
     */
    public function burnPasswordCheck(string $password): void
    {
        password_verify(
            $password,
            '$2y$12$3/UKOSy1vSycdBv8Nt2dW.Czh7tmpwxCskoQf/lL7cGJNnHrOWX7.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGES
    |--------------------------------------------------------------------------
    */

    public function lockoutMessage(): string
    {
        $minutes = (int) ceil($this->lockedSeconds() / 60);

        return 'Too many failed login attempts. '
            . 'Please try again in ' . max(1, $minutes) . ' '
            . ($minutes > 1 ? 'minutes' : 'minute') . '.';
    }

    public function failedMessage(): string
    {
        $message = 'Invalid email/WhatsApp number or password.';

        $remaining = $this->remainingAttempts();

        // Peringatan hanya ditampilkan saat percobaan hampir habis.
        if ($remaining > 0 && $remaining <= 2) {
            $message .= ' ' . $remaining . ' '
                . ($remaining > 1 ? 'attempts' : 'attempt')
                . ' remaining before your login is temporarily locked.';
        }

        return $message;
    }
}
