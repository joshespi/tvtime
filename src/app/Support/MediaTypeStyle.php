<?php

namespace App\Support;

/**
 * The show/movie colour pair and wording, in one place — same reasoning as
 * ButtonStyle. It lives here rather than inside <x-media-type-badge>
 * because the search typeahead renders its badge client-side (Alpine picks the
 * palette from the suggestion's type) and so needs the class strings as values,
 * and because the calendar paints its day chips with the same two colours.
 */
class MediaTypeStyle
{
    private const PALETTES = [
        'movie' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-300',
        'show' => 'bg-teal-100 dark:bg-teal-500/10 text-teal-800 dark:text-teal-300',
    ];

    private const LABELS = [
        'movie' => 'Movie',
        'show' => 'TV',
    ];

    public static function classes(string $type): string
    {
        return self::PALETTES[$type] ?? self::PALETTES['show'];
    }

    /** The full pill — palette plus shape — shared by <x-media-type-badge> and the typeahead. */
    public static function badgeClasses(string $type): string
    {
        return 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide '.self::classes($type);
    }

    /** @return array<string, array{class: string, label: string}> every type's badge, keyed by type, for client-side lookup. */
    public static function badges(): array
    {
        return collect(self::LABELS)->map(fn ($label, $type) => [
            'class' => self::badgeClasses($type),
            'label' => $label,
        ])->all();
    }

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? self::LABELS['show'];
    }

    /** The user's own list for this type — where a catalog page belongs in the nav. */
    public static function indexRoute(string $type): string
    {
        return $type === 'movie' ? 'movies.index' : 'shows.index';
    }
}
