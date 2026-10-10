<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserImportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $password
    ) {
        $this->password = $password;
    }

    /**
     * Notification channels.
     */
    public function via(
        object $notifiable
    ): array {
        return [
            'mail',
        ];
    }

    /**
     * Build email.
     */
    public function toMail(
        object $notifiable
    ): MailMessage {

        $verificationUrl =
            URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(
                    config(
                        'auth.verification.expire',
                        60
                    )
                ),
                [
                    'id' =>
                        $notifiable->getKey(),

                    'hash' =>
                        sha1(
                            $notifiable
                                ->getEmailForVerification()
                        ),
                ]
            );

        return (new MailMessage)
            ->subject(
                'Akun VASETRA Anda'
            )
            ->view(
                'emails.auth.user-created',
                [
                    'url' =>
                        $verificationUrl,

                    'user' =>
                        $notifiable,

                    'password' =>
                        $this->password,
                ]
            );
    }
}