<?php

namespace App\Notifications;

use App\Models\Episode;
use App\Notifications\Concerns\TracksNotificationSubject;
use App\Support\TmdbImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewEpisodeAired extends Notification implements ShouldQueue, TracksNotificationSubject
{
    // SerializesModels so the queued job carries the episode's id (re-hydrated
    // on the worker) rather than the whole Episode instance — without it, every
    // subscriber's job serializes the full model (and its loaded `show`) into
    // the jobs table instead of a lightweight reference.
    use Queueable, SerializesModels;

    public function __construct(private readonly Episode $episode) {}

    public function subject(): Episode
    {
        return $this->episode;
    }

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
            ->icon($show->posterUrl(TmdbImage::SIZE_ICON) ?? TmdbImage::FALLBACK_ICON)
            ->data(['url' => route('dashboard')])
            ->tag("episode-{$this->episode->id}");
    }
}
