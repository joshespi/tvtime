<?php

namespace App\Services\Catalog;

use App\Services\Shows\TmdbClient;
use App\Services\Shows\TraktClient;
use App\Support\TmdbImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Backs the combined "add a show or movie" search box — same TMDB-primary/
 * Trakt-fallback policy as ShowSyncService, spanning both media types.
 */
class CatalogSearchService
{
    public function __construct(
        private readonly TmdbClient $tmdb,
        private readonly TraktClient $trakt,
    ) {}

    /** How long an identical catalog search is reused before re-hitting a provider. */
    private const CACHE_TTL_MINUTES = 60;

    /**
     * @return array{source: ?string, results: array<int, array>}
     */
    public function search(string $query): array
    {
        // The same query gets re-issued constantly — typeahead then form submit,
        // then again on every sort change and reload, since <x-sort-select>
        // carries ?q= forward. Results are static for far longer than this TTL.
        return Cache::remember(
            'catalog:search:'.md5(mb_strtolower($query)),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($query) {
                $source = 'tmdb';
                $results = $this->tmdb->searchMulti($query);

                if ($results === []) {
                    Log::info('TMDB multi-search returned no results, falling back to Trakt', ['query' => $query]);

                    $source = 'trakt';
                    $results = $this->trakt->searchMulti($query);
                }

                $results = collect($results)
                    ->map(fn (array $result) => [...$result, 'poster_url' => TmdbImage::url($result['poster_path'])])
                    ->all();

                return ['source' => $source, 'results' => $results];
            }
        );
    }

    /**
     * Convenience for the /shows, /movies, and /dashboard pages, which each embed
     * <x-catalog-search> and need the same "trim ?q=, search unless blank" dance.
     *
     * @return array{query: string, source: ?string, results: array<int, array>}
     */
    public function forRequest(Request $request, string $param = 'q'): array
    {
        $query = $request->string($param)->trim()->toString();

        return [
            'query' => $query,
            ...($query !== '' ? $this->search($query) : ['source' => null, 'results' => []]),
        ];
    }
}
