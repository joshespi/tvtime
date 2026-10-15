<?php

namespace App\Support;

/**
 * TMDB poster URL formatting. Stateless — lives here (not on Show/Movie)
 * because callers like CatalogSearchService need it for combined show+movie
 * results before either model is in hand.
 */
class TmdbImage
{
    /** Poster size for list cards and cast headshots. */
    public const SIZE_CARD = 'w185';

    /** Poster size for a title's own detail page. */
    public const SIZE_DETAIL = 'w342';

    /** Small size for push notification icons. */
    public const SIZE_ICON = 'w92';

    /** Shown when a title has no poster. */
    public const FALLBACK_ICON = '/icon-192.png';

    public static function url(?string $posterPath, string $size = self::SIZE_CARD): ?string
    {
        if (! $posterPath) {
            return null;
        }

        return rtrim(config('services.tmdb.image_base_url'), '/')."/{$size}{$posterPath}";
    }
}
