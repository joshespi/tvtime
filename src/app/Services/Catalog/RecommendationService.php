<?php

namespace App\Services\Catalog;

use App\Models\User;
use App\Services\Shows\TmdbClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Backs the "Recommended for you" section on the dashboard, seeded from a
 * random sample of the user's tracked shows and movies with anything already
 * tracked filtered out.
 */
class RecommendationService
{
    /** How long a seed item's recommendations are reused before re-hitting TMDB. */
    private const CACHE_TTL_MINUTES = 60 * 12;

    /** How many of the user's shows/movies (picked at random) feed the recommendations. */
    private const SEED_LIMIT = 5;

    private const RESULT_LIMIT = 12;

    /** How much wider than RESULT_LIMIT the rated candidate pool is before it's shuffled — gives the shuffle room to vary the display without dipping into poorly-rated results. */
    private const CANDIDATE_POOL_MULTIPLIER = 3;

    public function __construct(private readonly TmdbClient $tmdb) {}

    /** @return array<int, array> */
    public function forUser(User $user): array
    {
        if (! $user->recommendations_enabled) {
            return [];
        }

        // One join per type for just the columns read here — tmdb_id, plus the
        // show status that picks the seeds — so the wide catalog columns
        // (overview, genres JSON, posters) stay in the database.
        $showRows = $user->shows()->join('shows', 'shows.id', '=', 'user_shows.show_id')->get(['shows.tmdb_id', 'user_shows.status']);
        $showIds = $this->tmdbIds($showRows);
        $movieIds = $this->tmdbIds($user->movies()->join('movies', 'movies.id', '=', 'user_movies.movie_id')->get(['movies.tmdb_id']));

        // Everything tracked or dismissed is filtered out of the results, paused
        // and dropped shows included — those were deliberate no's, so they'd be
        // the worst possible suggestions. Only actively-watched ones seed the
        // lookup. "Not interested" persists across cache misses the same way, so
        // a dismissed title can't reappear just because its seed rotated out and
        // back in, or the 12h cache expired.
        $dismissed = $user->dismissedRecommendations()->get(['type', 'tmdb_id'])->groupBy('type');
        $excluded = [
            'show' => $showIds->concat($dismissed->get('show', collect())->pluck('tmdb_id'))->all(),
            'movie' => $movieIds->concat($dismissed->get('movie', collect())->pluck('tmdb_id'))->all(),
        ];

        $seedShowIds = $this->tmdbIds($showRows->filter(fn ($userShow) => $userShow->status->isActive()));

        $seeds = $this->seeds($seedShowIds, 'show')
            ->concat($this->seeds($movieIds, 'movie'))
            ->all();

        // The underlying fetch is cached for 12h, so a plain sort-and-take would
        // show the same results on every visit within that window. Narrow to a
        // decent-rated pool, then shuffle *per request* (not cached) so reloading
        // the dashboard surfaces a different mix without needing to re-hit TMDB.
        return collect($this->fetch($seeds))
            ->reject(fn (array $result) => in_array($result['id'], $excluded[$result['type']], true))
            ->unique(fn (array $result) => $result['type'].':'.$result['id'])
            ->sortByDesc('vote_average')
            ->take(self::RESULT_LIMIT * self::CANDIDATE_POOL_MULTIPLIER)
            ->shuffle()
            ->take(self::RESULT_LIMIT)
            ->values()
            ->all();
    }

    /**
     * Distinct TMDB ids off a user's tracked rows.
     *
     * @param  Collection<int, Model>  $rows
     * @return Collection<int, string>
     */
    private function tmdbIds(Collection $rows): Collection
    {
        return $rows->pluck('tmdb_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    /**
     * A random SEED_LIMIT of the given ids, not just the most recent — with a
     * user who tracks more than a handful of titles, always seeding off the
     * same few would mean always recommending off the same few. Randomizing
     * which titles seed the lookup (each request, not cached) is what makes
     * "Recommended for you" actually rotate across visits instead of just
     * shuffling the display order of one fixed candidate pool.
     *
     * @param  Collection<int, string>  $ids
     * @return Collection<int, array{type: string, id: string}>
     */
    private function seeds(Collection $ids, string $type): Collection
    {
        return $ids->random(min($ids->count(), self::SEED_LIMIT))
            ->map(fn (string $id) => ['type' => $type, 'id' => $id])
            ->values();
    }

    /**
     * Recommendations for every seed, served from cache where possible and
     * fetched in one concurrent batch for the rest — this runs while the
     * dashboard renders, so the misses must not queue up behind each other.
     *
     * @param  array<int, array{type: string, id: string}>  $seeds
     * @return array<int, array>
     */
    private function fetch(array $seeds): array
    {
        // One read and one write for the whole batch rather than per seed: the
        // cache store here is the database, so each Cache::get would otherwise
        // be its own round trip on the dashboard's render path.
        $keys = array_map(fn (array $seed) => $this->cacheKey($seed), $seeds);
        $cached = Cache::many($keys);

        $results = [];
        $misses = [];

        foreach ($seeds as $index => $seed) {
            $hit = $cached[$keys[$index]] ?? null;

            if ($hit === null) {
                $misses[] = $seed;

                continue;
            }

            $results[] = $hit;
        }

        $fetched = $this->tmdb->recommendations($misses);

        // null marks a seed whose TMDB fetch failed (see TmdbClient::recommendations()'s
        // docblock) — skipped here rather than cached, so one blip doesn't blank that
        // seed's recommendations out for the full 12h TTL. It's also left out of
        // $results: there's nothing to show for it on this request either, but the
        // next visit gets a clean retry instead of a cached empty list.
        $toCache = [];

        foreach ($fetched as $index => $result) {
            if ($result === null) {
                continue;
            }

            $toCache[$this->cacheKey($misses[$index])] = $result;
            $results[] = $result;
        }

        if ($toCache !== []) {
            Cache::putMany($toCache, now()->addMinutes(self::CACHE_TTL_MINUTES));
        }

        return array_merge(...$results ?: [[]]);
    }

    /** @param  array{type: string, id: string}  $seed */
    private function cacheKey(array $seed): string
    {
        return "recommendations:{$seed['type']}:{$seed['id']}";
    }
}
