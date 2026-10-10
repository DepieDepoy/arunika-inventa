<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CalendarMeetingCancellation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CalendarEvent $event,
        public string $organizerName = 'Penyelenggara'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Meeting Dibatalkan: ' . $this->event->title)
            ->view('emails.auth.meeting-cancelled', [
                'event' => $this->event,
                'organizer' => $this->organizerName,
                'recipient' => $notifiable,
            ]);
    }

}

