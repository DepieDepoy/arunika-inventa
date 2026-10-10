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
     * Kirim melalui email dan simpan ke database notifications.
     */
    public function via(object $notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }

    /**
     * Data untuk notifikasi di lonceng navbar.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'calendar_invitation',
            'title' => 'Undangan Meeting',
            'message' => $this->organizer->name
                . ' mengundang Anda ke meeting: '
                . $this->event->title,

            'event_id' => $this->event->id,
            'organizer_id' => $this->organizer->id,

            // Sesuaikan jika nama kolom tanggal berbeda.
            'start_at' => $this->event->start_at,

            'url' => route('calendar.index'),
        ];
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
