<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * How a number of viewings reads in prose — shared by the movie cards, the
 * catalog search results and the watch history so they can't word the same
 * fact differently.
 */
class WatchCount
{
    /** e.g. "once", "twice", "3 times". */
    public static function phrase(int $times): string
    {
        return match ($times) {
            1 => 'once',
            2 => 'twice',
            default => "{$times} times",
        };
    }

    /**
     * The whole sentence, e.g. "Watched twice · last on May 3, 2025" — or
     * "Watched twice before" when the movie has been re-queued and so has a
     * history but no current watched date. Null when it's never been seen.
     */
    public static function seen(int $times, ?CarbonInterface $lastWatchedAt): ?string
    {
        if ($times < 1 && ! $lastWatchedAt) {
            return null;
        }

        $seen = $times > 0 ? 'Watched '.self::phrase($times) : 'Watched';

        return $lastWatchedAt
            ? $seen.' · last on '.$lastWatchedAt->format('M j, Y')
            : $seen.' before';
    }
}
