<?php

namespace App\Support;

/**
 * TMDB poster URL formatting. Stateless — lives here (not on Show/Movie)
 * because callers like CatalogSearchService need it for combined show+movie
 * results before either model is in hand.
 */
class TmdbImage
{
    public static function url(?string $posterPath, string $size = 'w185'): ?string
    {
        if (! $posterPath) {
            return null;
        }

        return rtrim(config('services.tmdb.image_base_url'), '/')."/{$size}{$posterPath}";
    }
}
