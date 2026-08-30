<?php

namespace App\Support;

/**
 * The show/movie colour pair and wording, in one place — same reasoning as
 * [[ButtonStyle]]. It lives here rather than inside <x-media-type-badge>
 * because the search typeahead renders its badge client-side (Alpine picks the
 * palette from the suggestion's type) and so needs the class strings as values,
 * and because the calendar paints its day chips with the same two colours.
 */
class MediaTypeStyle
{
    private const PALETTES = [
        'movie' => 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400',
        'show' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
    ];

    private const LABELS = [
        'movie' => 'Movie',
        'show' => 'TV',
    ];

    public static function classes(string $type): string
    {
        return self::PALETTES[$type] ?? self::PALETTES['show'];
    }

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? self::LABELS['show'];
    }
}
