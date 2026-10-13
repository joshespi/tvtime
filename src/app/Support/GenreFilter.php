<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Shared single-genre filter used by both /shows and /movies — same "All
 * genres" default and matching logic, just pointed at different genre
 * accessors. Mirrors [[WatchlistSort]]'s shape.
 */
class GenreFilter
{
    /**
     * Applies the request's ?genre= to the items. Options are read off the full
     * set, before filtering, so picking a genre never removes it from its own
     * dropdown — and they're what the requested genre is validated against.
     *
     * @return array{0: Collection, 1: ?string, 2: array<string, string>} [filtered items, genre, options]
     */
    public static function fromRequest(Request $request, Collection $items, Closure $genresAccessor): array
    {
        $options = static::options($items, $genresAccessor);
        $genre = static::resolve($request->string('genre')->toString(), $options);

        return [static::apply($items, $genre, $genresAccessor), $genre, $options];
    }

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
