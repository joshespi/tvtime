<?php

namespace App\Support;

use App\Models\NotificationSent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Shared "notify each recipient at most once" bookkeeping for the daily
 * check-new-episodes / check-movie-releases commands, which otherwise both
 * reimplement the same build-a-lookup-set / notify-and-record shape.
 */
class DedupedNotifier
{
    /**
     * Build a "{user_id}:{subject_type}:{subject_id}" lookup set of notifications
     * already sent, regardless of channel (all of a notification's channels are
     * dispatched together in a single notify() call, so any recorded channel
     * means sent).
     *
     * @param  EloquentCollection<int, Model>|iterable<Model>  $subjects
     */
    public static function alreadySent(iterable $subjects): Collection
    {
        $subjects = collect($subjects);

        if ($subjects->isEmpty()) {
            return collect();
        }

        // Grouped by morph type and OR'd rather than one whereIn(id) + one
        // whereIn(type): those two lists aren't paired, so a mixed-type
        // $subjects (an episode and a movie sharing a numeric id) would let an
        // unrelated row's type match an unrelated row's id and read as sent.
        $byType = $subjects->groupBy(fn (Model $s) => $s->getMorphClass());

        return NotificationSent::query()
            ->where(function ($query) use ($byType) {
                foreach ($byType as $subjectType => $group) {
                    $query->orWhere(function ($query) use ($subjectType, $group) {
                        $query->where('subject_type', $subjectType)
                            ->whereIn('subject_id', $group->map(fn (Model $s) => $s->getKey()));
                    });
                }
            })
            ->get(['user_id', 'subject_type', 'subject_id'])
            ->map(fn (NotificationSent $sent) => self::key($sent->user_id, $sent->subject_type, $sent->subject_id))
            ->unique()
            ->flip();
    }

    /**
     * Queue $user's notification about $subject unless already recorded in
     * $alreadySent.
     *
     * The actual notifications_sent row is no longer written here — see
     * App\Listeners\RecordNotificationSent — because $user->notify() only
     * queues one job per channel; recording "sent" at that point means a job
     * that later exhausts its retries (a mail-server blip, a dead push
     * endpoint) still permanently reads as delivered. The listener writes the
     * row once a channel actually completes, so a fully-failed send is retried
     * on the next command run instead of being silently swallowed forever.
     *
     * @return bool Whether a notification was actually queued.
     */
    public static function notifyOnce(
        Collection $alreadySent,
        User $user,
        Notification $notification,
        Model $subject,
    ): bool {
        $key = self::key($user->getKey(), $subject->getMorphClass(), $subject->getKey());

        if ($alreadySent->has($key)) {
            return false;
        }

        $user->notify($notification);

        // Recorded back into the same lookup the caller is iterating with, so a
        // subject with more than one subscriber in the same run can't be queued
        // twice — alreadySent is built once up front from the database, before
        // any of this run's sends have landed (or, now, even completed).
        $alreadySent->put($key, true);

        return true;
    }

    private static function key(int|string $userId, string $subjectType, int|string $subjectId): string
    {
        return "{$userId}:{$subjectType}:{$subjectId}";
    }
}
