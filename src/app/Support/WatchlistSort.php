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
    /**
     * Value ⇒ label. The select renders straight off this, so a new sort is one
     * edit here rather than a value in this class and a label in the template —
     * same reasoning as TrackingStatus::label() driving its own select.
     */
    private const OPTIONS = [
        'added' => 'Recently added',
        'release' => 'Release date',
        'title' => 'Title (A–Z)',
    ];

    public static function options(): array
    {
        return self::OPTIONS;
    }

    public static function resolve(?string $sort): string
    {
        return $sort !== null && isset(self::OPTIONS[$sort]) ? $sort : 'added';
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
