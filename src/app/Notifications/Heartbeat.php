<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Heartbeat extends Notification
{
    // Not queued: the scheduler sends it directly, so a broken mail setup
    // fails the scheduled run instead of disappearing into the queue.
    public function __construct(private readonly string $url) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').' heartbeat')
            ->line("Scheduled check-in so the mail account doesn't get deactivated for inactivity.")
            ->action('Open '.config('app.name'), $this->url)
            ->line('Nothing to do here. If this stops showing up, mail is broken.');
    }
}
