<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;
    use \App\Notifications\Concerns\InRecipientLocale;

    /**
     * Notification channels.
     */
    public function via(object $notifiable): array
    {
        $this->useRecipientLocale($notifiable);

        return ['mail'];
    }

    /**
     * Email verification message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(
                config('auth.verification.expire', 60)
            ),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1(
                    $notifiable->getEmailForVerification()
                ),
            ]
        );

        return (new MailMessage)
            ->subject(__('Verifikasi Email Akun VASETRA'))
            ->view('emails.auth.verify-email', [
                'url' => $verificationUrl,
                'user' => $notifiable,
            ]);
    }
}