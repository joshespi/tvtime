<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Collection;

/**
 * Shared "added / release / title" sort used by both /shows and /movies —
 * same three options and matching logic, just pointed at different date
 * and title accessors.
 */
class WatchlistSort
{
    private const OPTIONS = ['added', 'release', 'title'];

    public static function resolve(?string $sort): string
    {
        return in_array($sort, self::OPTIONS, true) ? $sort : 'added';
    }

    public static function apply(Collection $items, string $sort, Closure $dateAccessor, Closure $titleAccessor): Collection
    {
        return match ($sort) {
            'release' => $items->sortByDesc($dateAccessor)->values(),
            'title' => $items->sortBy(fn ($item) => mb_strtolower($titleAccessor($item)))->values(),
            default => $items->sortByDesc('created_at')->values(),
        };
    }
}
