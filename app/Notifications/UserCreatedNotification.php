<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserCreatedNotification extends Notification
{
    use Queueable;

    protected string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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
            ->subject('Akun VASETRA Anda')
            ->view('emails.auth.user-created', [
                'url' => $verificationUrl,
                'user' => $notifiable,
                'password' => $this->password,
            ]);
    }
}
