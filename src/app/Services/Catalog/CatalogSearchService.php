<?php

namespace App\Services\Catalog;

use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Services\Shows\TmdbClient;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        // then again on every sort change and reload, since <x-query-select>
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
    public function forRequest(Request $request): array
    {
        $query = $request->string('q')->trim()->toString();

        return [
            'query' => $query,
            'results' => $query !== '' ? $this->annotate($this->search($query), $request->user()) : [],
        ];
    }

    /**
     * Tags each result with what the user already has, so a movie they finished
     * years ago doesn't come back looking brand new. Applied after the cache on
     * purpose — the TMDB results are shared between users, this part isn't.
     *
     * @param  array<int, array>  $results
     * @return array<int, array>
     */
    private function annotate(array $results, ?User $user): array
    {
        if ($results === [] || ! $user) {
            return $results;
        }

        $ids = collect($results)->groupBy('type')->map(fn (Collection $group) => $group->pluck('id')->all());

        $movies = $this->trackedByTmdbId($user->movies()->withCount('watches'), 'movie', $ids->get('movie', []));
        $shows = $this->trackedByTmdbId($user->shows(), 'show', $ids->get('show', []));

        return array_map(function (array $result) use ($movies, $shows) {
            $result['tracked'] = $result['type'] === 'movie'
                ? $this->movieState($movies->get((string) $result['id']))
                : $this->showState($shows->get((string) $result['id']));

            return $result;
        }, $results);
    }

    /**
     * The user's rows for the given TMDB ids, keyed by TMDB id. Only the id
     * columns are selected — nothing here renders the stored metadata.
     *
     * Public so CatalogController can reuse it for its own single-id "is this
     * already tracked?" lookup, rather than re-deriving the same whereHas query.
     *
     * @param  HasMany<UserMovie|UserShow, User>  $relation
     * @param  array<int, string>  $tmdbIds
     * @return Collection<string, UserMovie|UserShow>
     */
    public function trackedByTmdbId(HasMany $relation, string $name, array $tmdbIds): Collection
    {
        if ($tmdbIds === []) {
            return collect();
        }

        return $relation
            ->whereHas($name, fn ($query) => $query->whereIn('tmdb_id', $tmdbIds))
            ->with("{$name}:id,tmdb_id")
            ->get()
            ->keyBy(fn ($row) => (string) $row->{$name}->tmdb_id);
    }

    /**
     * Both states describe themselves the same way — a label plus at most one
     * follow-up action — so the card renders them with a single branch instead
     * of probing for whichever url key each type happens to set.
     *
     * @return array{label: string, action?: array{label: string, url: string, post: bool}}|null
     */
    private function movieState(?UserMovie $userMovie): ?array
    {
        if (! $userMovie) {
            return null;
        }

        // Same sentence the movie cards render, so a title can't describe itself
        // one way in search results and another on the watchlist.
        $seen = $userMovie->seenPhrase();

        if (! $userMovie->isFinished()) {
            return ['label' => 'On your watchlist'.($seen ? " · {$seen}" : '')];
        }

        return [
            'label' => (string) $seen,
            'action' => [
                'label' => 'Watch again',
                'url' => route('movies.watch-again', $userMovie),
                'post' => true,
            ],
        ];
    }

    /** @return array{label: string, action?: array{label: string, url: string, post: bool}}|null */
    private function showState(?UserShow $userShow): ?array
    {
        if (! $userShow) {
            return null;
        }

        return [
            'label' => "In My Shows · {$userShow->status->label()}",
            'action' => [
                'label' => 'View',
                'url' => route('shows.show', $userShow->show_id),
                'post' => false,
            ],
        ];
    }
}
