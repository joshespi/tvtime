<?php

namespace App\Support;

/** IMDB URL formatting, mirrors TmdbImage's role for poster URLs. */
class ImdbLink
{
    public static function personUrl(?string $imdbId): ?string
    {
        return self::url('name', $imdbId);
    }

    public static function titleUrl(?string $imdbId): ?string
    {
        return self::url('title', $imdbId);
    }

    private static function url(string $kind, ?string $imdbId): ?string
    {
        return $imdbId ? "https://www.imdb.com/{$kind}/{$imdbId}/" : null;
    }
}
