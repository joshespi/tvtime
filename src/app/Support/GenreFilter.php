<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Collection;

/**
 * Shared single-genre filter used by both /shows and /movies — same "All
 * genres" default and matching logic, just pointed at different genre
 * accessors. Mirrors [[WatchlistSort]]'s shape.
 */
class GenreFilter
{
    /**
     * The genre to filter by, or null for "no filter".
     *
     * Validated against the options actually on offer, so a ?genre= that nothing
     * carries any more — the last show with it was untracked, say — falls back to
     * unfiltered. Without that the select would show "All genres" (no option
     * matches, so the browser picks the first) over a list filtered to nothing.
     *
     * @param  array<string, string>  $options  From options(), keyed by genre.
     */
    public static function resolve(?string $genre, array $options): ?string
    {
        if ($genre === null || $genre === '') {
            return null;
        }

        return isset($options[$genre]) ? $genre : null;
    }

    /**
     * Select-ready value ⇒ label map of every genre present across the given
     * items, alphabetized. Computed before filtering so the option list doesn't
     * shrink once a genre is picked. The empty-string key is the "no filter"
     * option, kept here so the wording lives beside the values it labels.
     */
    public static function options(Collection $items, Closure $genresAccessor): array
    {
        $genres = $items->flatMap($genresAccessor)->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return ['' => 'All genres'] + array_combine($genres, $genres);
    }

    public static function apply(Collection $items, ?string $genre, Closure $genresAccessor): Collection
    {
        if ($genre === null) {
            return $items;
        }

        return $items->filter(
            fn ($item) => in_array($genre, $genresAccessor($item), true)
        )->values();
    }
}
