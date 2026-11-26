<?php

namespace App\Notifications;

use App\Models\Movie;
use App\Notifications\Concerns\TracksNotificationSubject;
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
        // Push only for users with a subscription: otherwise the queued push
        // job just re-hydrates everything to send nothing.
        return $notifiable->pushSubscriptions->isNotEmpty()
            ? ['mail', WebPushChannel::class]
            : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline())
            ->line($this->body())
            ->action('Open My Movies', route('movies.index'));
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->headline())
            ->body($this->body())
            ->icon($this->movie->iconUrl())
            ->data(['url' => route('movies.index')])
            ->tag("movie-{$this->movie->id}");
    }

    private function headline(): string
    {
        return "Released today: {$this->movie->title}";
    }

    private function body(): string
    {
        return "\"{$this->movie->title}\" is out today.";
    }
}
