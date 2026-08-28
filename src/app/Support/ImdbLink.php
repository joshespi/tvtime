<?php

namespace App\Support;

/** IMDB URL formatting, mirrors TmdbImage's role for poster URLs. */
class ImdbLink
{
    public static function personUrl(?string $imdbId): ?string
    {
        if (! $imdbId) {
            return null;
        }

        return "https://www.imdb.com/name/{$imdbId}/";
    }
}
