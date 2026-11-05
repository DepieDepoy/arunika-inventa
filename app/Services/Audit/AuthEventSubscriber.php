<?php

namespace App\Services\Audit;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

/**
 * Mencatat event autentikasi ke audit trail.
 *
 * Sengaja diletakkan di luar app/Listeners agar tidak ikut
 * auto-discovery Laravel (mencegah log tercatat dua kali).
 * Didaftarkan di AppServiceProvider.
 */
class AuthEventSubscriber
{
    public function onLogin(Login $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        AuditLogger::log(
            event: 'login',
            module: 'auth',
            description: $event->remember
                ? 'Login ke dashboard (remember me)'
                : 'Login ke dashboard',
            subject: $event->user,
            actor: $event->user,
        );
    }

    public function onLogout(Logout $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        AuditLogger::log(
            event: 'logout',
            module: 'auth',
            description: 'Logout dari dashboard',
            subject: $event->user,
            actor: $event->user,
        );
    }

    public function onRegistered(Registered $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        $companyName = $event->user->company?->company_name;

        AuditLogger::log(
            event: 'registered',
            module: 'auth',
            description: $companyName
                ? 'Registrasi akun baru untuk perusahaan "' . $companyName . '"'
                : 'Registrasi akun baru',
            subject: $event->user,
            actor: $event->user,
        );
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        AuditLogger::log(
            event: 'password_reset',
            module: 'auth',
            description: 'Reset password melalui link lupa password',
            subject: $event->user,
            actor: $event->user,
        );
    }

    public function onVerified(Verified $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        AuditLogger::log(
            event: 'email_verified',
            module: 'auth',
            description: 'Verifikasi email berhasil',
            subject: $event->user,
            actor: $event->user,
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            Registered::class => 'onRegistered',
            PasswordReset::class => 'onPasswordReset',
            Verified::class => 'onVerified',
        ];
    }
}
