<?php

namespace Tests\Unit\Support;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\NotificationSent;
use App\Models\User;
use App\Notifications\NewEpisodeAired;
use App\Support\DedupedNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DedupedNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_already_sent_keys_by_user_and_subject(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        NotificationSent::factory()->create([
            'user_id' => $user->id,
            'subject_type' => Episode::class,
            'subject_id' => $episode->id,
            'channel' => 'mail',
        ]);

        $alreadySent = DedupedNotifier::alreadySent([$episode]);

        $this->assertTrue($alreadySent->has("{$user->id}:".Episode::class.":{$episode->id}"));
    }

    public function test_already_sent_is_empty_without_querying_for_no_subjects(): void
    {
        $this->assertTrue(DedupedNotifier::alreadySent([])->isEmpty());
    }

    public function test_notify_once_sends_when_not_already_sent(): void
    {
        // notifications_sent is no longer written by notifyOnce() itself — see
        // App\Listeners\RecordNotificationSent — so under Notification::fake()
        // (which swaps out real delivery entirely) no row appears here. That
        // listener's own test covers the recording; this one covers dispatch.
        Notification::fake();

        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $sent = DedupedNotifier::notifyOnce(
            collect(),
            $user,
            new NewEpisodeAired($episode),
            $episode,
        );

        $this->assertTrue($sent);
        Notification::assertSentTo($user, NewEpisodeAired::class);
    }

    public function test_notify_once_skips_when_already_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $alreadySent = collect(["{$user->id}:".Episode::class.":{$episode->id}" => true]);

        $sent = DedupedNotifier::notifyOnce(
            $alreadySent,
            $user,
            new NewEpisodeAired($episode),
            $episode,
        );

        $this->assertFalse($sent);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notifications_sent', 0);
    }

    public function test_notify_once_records_into_the_same_lookup_to_prevent_a_double_send_within_one_run(): void
    {
        // Regression: notifyOnce() used to leave $alreadySent untouched after
        // queueing, so calling it twice for the same (user, subject) pair in
        // one run — the shape both console commands loop in — queued the
        // notification twice.
        Notification::fake();

        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        $alreadySent = collect();

        $first = DedupedNotifier::notifyOnce($alreadySent, $user, new NewEpisodeAired($episode), $episode);
        $second = DedupedNotifier::notifyOnce($alreadySent, $user, new NewEpisodeAired($episode), $episode);

        $this->assertTrue($first);
        $this->assertFalse($second);
        Notification::assertSentToTimes($user, NewEpisodeAired::class, 1);
    }

    public function test_notifications_for_different_media_types_do_not_collide(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        // Same numeric id, different subject type — the type must be part of the key.
        NotificationSent::factory()->create([
            'user_id' => $user->id,
            'subject_type' => Movie::class,
            'subject_id' => $episode->id,
            'channel' => 'mail',
        ]);

        $alreadySent = DedupedNotifier::alreadySent([$episode]);

        $this->assertFalse($alreadySent->has("{$user->id}:".Episode::class.":{$episode->id}"));
    }
}
