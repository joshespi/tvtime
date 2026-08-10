<?php

namespace App\Notifications;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewEpisodeAired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Episode $episode) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', WebPushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $show = $this->episode->show;

        return (new MailMessage)
            ->subject("New episode: {$show->title}")
            ->line("{$this->episode->label()} of \"{$show->title}\" just aired.")
            ->action('Open Up Next', route('dashboard'));
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $show = $this->episode->show;

        return (new WebPushMessage)
            ->title("New episode: {$show->title}")
            ->body("{$this->episode->label()} just aired.")
            ->icon($show->posterUrl('w92') ?? '/icon-192.png')
            ->data(['url' => route('dashboard')])
            ->tag("episode-{$this->episode->id}");
    }
}
