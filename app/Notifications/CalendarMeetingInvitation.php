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
    use \App\Notifications\Concerns\InRecipientLocale;

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
        $this->useRecipientLocale($notifiable);

        return ['mail'];
    }

    /**
     * Email undangan meeting.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Undangan Meeting: :title', ['title' => $this->event->title]))
            ->view('emails.auth.meeting-invitation', [
                'event' => $this->event,
                'organizer' => $this->organizer,
                'recipient' => $notifiable,
            ]);
    }
}
