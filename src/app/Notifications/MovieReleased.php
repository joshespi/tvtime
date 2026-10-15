<?php

namespace App\Notifications;

use App\Models\Movie;
use App\Notifications\Concerns\TracksNotificationSubject;
use App\Support\TmdbImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MovieReleased extends Notification implements ShouldQueue, TracksNotificationSubject
{
    // See NewEpisodeAired's comment — same reasoning for Movie.
    use Queueable, SerializesModels;

    public function __construct(private readonly Movie $movie) {}

    public function subject(): Movie
    {
        return $this->movie;
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
        return (new MailMessage)
            ->subject("Released today: {$this->movie->title}")
            ->line("\"{$this->movie->title}\" is out today.")
            ->action('Open My Movies', route('movies.index'));
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("Released today: {$this->movie->title}")
            ->body("\"{$this->movie->title}\" is out today.")
            ->icon($this->movie->posterUrl(TmdbImage::SIZE_ICON) ?? TmdbImage::FALLBACK_ICON)
            ->data(['url' => route('movies.index')])
            ->tag("movie-{$this->movie->id}");
    }
}
