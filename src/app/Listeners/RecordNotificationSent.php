<?php

namespace App\Listeners;

use App\Models\NotificationSent;
use App\Notifications\Concerns\TracksNotificationSubject;
use Illuminate\Notifications\Events\NotificationSent as NotificationSentEvent;

/**
 * Records notifications_sent once a notification has actually been delivered
 * on a channel — not when DedupedNotifier::notifyOnce() merely queues it.
 * Each channel a notification goes out on is its own queued job (see
 * Illuminate\Notifications\NotificationSender::queueNotification()), retried
 * independently by the worker; writing the row eagerly at dispatch time meant
 * a job that exhausted every retry (a mail-server blip, a dead push endpoint)
 * still permanently recorded that (user, subject) as notified, even though
 * nothing was ever delivered — silencing it forever with no error surfaced
 * anywhere. This fires only after Illuminate's NotificationSender has
 * actually sent the channel successfully.
 *
 * Auto-discovered by Laravel's event system (this class lives under
 * app/Listeners and its handle() is typed to the event it listens for) —
 * nothing to register in a service provider.
 *
 * Registered for every notification sent app-wide, not just NewEpisodeAired/
 * MovieReleased — TracksNotificationSubject is what scopes this down, so a
 * future unrelated notification (e.g. Laravel's own password-reset mail)
 * passes through untouched instead of needing this listener to know about it.
 */
class RecordNotificationSent
{
    public function handle(NotificationSentEvent $event): void
    {
        $notification = $event->notification;

        if (! $notification instanceof TracksNotificationSubject) {
            return;
        }

        $subject = $notification->subject();
        $now = now();

        // insertOrIgnore rather than insert: notifications_sent_unique is keyed
        // on (user, subject, channel) including channel, so this is only ever
        // a real duplicate if this exact channel's job runs to completion twice
        // for the same subject (e.g. a queue/infra hiccup re-delivers a job
        // whose prior attempt did complete) — insertOrIgnore makes that a
        // no-op instead of an uncaught exception inside the notification job.
        NotificationSent::insertOrIgnore([[
            'user_id' => $event->notifiable->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'channel' => $event->channel === 'mail' ? 'mail' : 'push',
            'sent_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]]);
    }
}
