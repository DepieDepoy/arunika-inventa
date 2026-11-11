<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CalendarMeetingInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CalendarEvent $event,
        public User $organizer
    ) {
    }

    /**
     * Hanya email. Notifikasi lonceng (web & mobile) dikirim terpisah oleh
     * CalendarController lewat GlobalNotification "Undangan rapat", agar
     * penerima tidak mendapat dua notifikasi untuk satu undangan.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Email undangan meeting.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan Meeting: ' . $this->event->title)
            ->view('emails.auth.meeting-invitation', [
                'event' => $this->event,
                'organizer' => $this->organizer,
                'recipient' => $notifiable,
            ]);
    }
}
