<?php

namespace Tests\Unit\Listeners;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\User;
use App\Notifications\MovieReleased;
use App\Notifications\NewEpisodeAired;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent as NotificationSentEvent;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class RecordNotificationSentTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorded_once_a_tracked_notification_is_actually_delivered(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        // Dispatched via the real event() helper rather than calling the
        // listener directly — this exercises Laravel's auto-discovery wiring
        // (the listener lives under app/Listeners with a typed handle()) the
        // same way a genuinely delivered mail/push notification would trigger
        // it, not just the listener class in isolation.
        event(new NotificationSentEvent($user, new NewEpisodeAired($episode), 'mail'));

        $this->assertDatabaseHas('notifications_sent', [
            'user_id' => $user->id,
            'subject_type' => Episode::class,
            'subject_id' => $episode->id,
            'channel' => 'mail',
        ]);
    }

    public function test_a_non_mail_channel_is_recorded_as_push(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        event(new NotificationSentEvent($user, new MovieReleased($movie), WebPushChannel::class));

        $this->assertDatabaseHas('notifications_sent', [
            'user_id' => $user->id,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'channel' => 'push',
        ]);
    }

    public function test_ignores_notifications_that_do_not_track_a_subject(): void
    {
        $user = User::factory()->create();

        // A notification that doesn't implement TracksNotificationSubject —
        // this listener fires for every notification sent app-wide, so it must
        // no-op for ones it has no bookkeeping to do for.
        event(new NotificationSentEvent($user, new VerifyEmail, 'mail'));

        $this->assertDatabaseCount('notifications_sent', 0);
    }

    public function test_does_not_throw_when_the_row_already_exists(): void
    {
        // Regression: a plain insert() would throw on notifications_sent_unique
        // if this exact channel's delivery is ever recorded twice (a queue/infra
        // hiccup re-delivering an already-completed job) — insertOrIgnore makes
        // that a no-op instead of an uncaught exception inside the notification job.
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        event(new NotificationSentEvent($user, new NewEpisodeAired($episode), 'mail'));
        event(new NotificationSentEvent($user, new NewEpisodeAired($episode), 'mail'));

        $this->assertDatabaseCount('notifications_sent', 1);
    }
}
