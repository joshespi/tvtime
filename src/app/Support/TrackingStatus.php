<?php

namespace App\Support;

/**
 * The per-user tracking state of a show, stored on `user_shows.status`.
 *
 * `Watching` and `Finished` are both live: they are the statuses that feed the
 * dashboard, the calendar, new-episode notifications and recommendation seeds.
 * A finished show stays live on purpose — that's how a returning series is
 * noticed at all, and UserShow::refreshFinishedStatus() flips it back to
 * Watching once one of its episodes airs unwatched. `Paused` and `Dropped`
 * both mean "keep the row, stop the noise" — the show stays in My Shows with
 * its watch history intact, but stops surfacing anywhere else. They differ only
 * in intent, so a show being come back to later reads differently from one that
 * was abandoned.
 */
enum TrackingStatus: string
{
    case Watching = 'watching';
    case Finished = 'finished';
    case Paused = 'paused';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Watching => 'Watching',
            self::Finished => 'Finished',
            self::Paused => 'Paused',
            self::Dropped => 'Dropped',
        };
    }

    /** Whether this status still produces dashboard entries and notifications. */
    public function isActive(): bool
    {
        return $this === self::Watching || $this === self::Finished;
    }

    /** @return array<int, string> The values scopeActive() matches. */
    public static function activeValues(): array
    {
        return array_column(array_filter(self::cases(), fn (self $status) => $status->isActive()), 'value');
    }
}
