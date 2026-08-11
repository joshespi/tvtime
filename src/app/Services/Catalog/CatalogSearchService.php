<?php

namespace App\Services\Catalog;

use App\Services\Shows\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Backs the combined "add a show or movie" search box, via TMDB's multi-search. */
class CatalogSearchService
{
    public function __construct(private readonly TmdbClient $tmdb) {}

    /** How long an identical catalog search is reused before re-hitting TMDB. */
    private const CACHE_TTL_MINUTES = 60;

    /** @return array<int, array> */
    public function search(string $query): array
    {
        // The same query gets re-issued constantly — typeahead then form submit,
        // then again on every sort change and reload, since <x-sort-select>
        // carries ?q= forward. Results are static for far longer than this TTL.
        return Cache::remember(
            'catalog:search:'.md5(mb_strtolower($query)),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->tmdb->searchMulti($query)
        );
    }

    /**
     * Convenience for the /shows, /movies, and /dashboard pages, which each embed
     * <x-catalog-search> and need the same "trim ?q=, search unless blank" dance.
     *
     * @return array{query: string, results: array<int, array>}
     */
    public function forRequest(Request $request, string $param = 'q'): array
    {
        $query = $request->string($param)->trim()->toString();

        return [
            'query' => $query,
            'results' => $query !== '' ? $this->search($query) : [],
        ];
    }
}
