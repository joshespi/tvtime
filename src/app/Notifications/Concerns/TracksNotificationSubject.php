<?php

namespace App\Notifications\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Implemented by notifications whose delivery App\Listeners\RecordNotificationSent
 * logs to notifications_sent. That listener runs for every notification sent
 * app-wide (it's a generic Illuminate\Notifications\Events\NotificationSent
 * listener), so it checks for this interface rather than special-casing
 * NewEpisodeAired/MovieReleased by class name — any future notification that
 * wants the same "notify each recipient at most once" bookkeeping just
 * implements this instead of the listener needing to know about it.
 */
interface TracksNotificationSubject
{
    /** The episode/movie/etc. this notification is about. */
    public function subject(): Model;
}
