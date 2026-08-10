<?php

namespace App\Support;

use App\Models\NotificationSent;
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

        return NotificationSent::query()
            ->whereIn('subject_id', $subjects->map(fn (Model $s) => $s->getKey()))
            ->whereIn('subject_type', $subjects->map(fn (Model $s) => $s->getMorphClass())->unique())
            ->get(['user_id', 'subject_type', 'subject_id'])
            ->map(fn (NotificationSent $sent) => self::key($sent->user_id, $sent->subject_type, $sent->subject_id))
            ->unique()
            ->flip();
    }

    /**
     * Notify $user about $subject unless already recorded in $alreadySent, then record it.
     *
     * @return bool Whether a notification was actually sent.
     */
    public static function notifyOnce(
        Collection $alreadySent,
        Model $user,
        Notification $notification,
        Model $subject,
    ): bool {
        $key = self::key($user->getKey(), $subject->getMorphClass(), $subject->getKey());

        if ($alreadySent->has($key)) {
            return false;
        }

        $user->notify($notification);

        // One multi-row insert rather than one round trip per channel — this runs
        // once per (subject × subscriber) pair, so the loop adds up fast.
        $now = now();

        NotificationSent::insert(
            collect($notification->via($user))
                ->map(fn (string $channel) => [
                    'user_id' => $user->getKey(),
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'channel' => $channel === 'mail' ? 'mail' : 'push',
                    'sent_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );

        return true;
    }

    private static function key(int|string $userId, string $subjectType, int|string $subjectId): string
    {
        return "{$userId}:{$subjectType}:{$subjectId}";
    }
}
