<?php

namespace App\Services\Catalog;

use App\Models\User;
use App\Services\Shows\TmdbClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Backs the "Recommended for you" section on the dashboard, seeded from the
 * user's most recently tracked shows and movies with anything already tracked
 * filtered out.
 */
class RecommendationService
{
    /** How long a seed item's recommendations are reused before re-hitting TMDB. */
    private const CACHE_TTL_MINUTES = 60 * 12;

    /** How many of the user's most recent shows/movies feed the recommendations. */
    private const SEED_LIMIT = 5;

    private const RESULT_LIMIT = 12;

    public function __construct(private readonly TmdbClient $tmdb) {}

    /** @return array<int, array> */
    public function forUser(User $user): array
    {
        if (! $user->recommendations_enabled) {
            return [];
        }

        // Only tmdb_id is ever read off the related records, so the wide columns
        // (overview, genres JSON, posters) stay in the database.
        $showRows = $user->shows()->with('show:id,tmdb_id')->get();
        $showIds = $this->tmdbIds($showRows, 'show');
        $movieIds = $this->tmdbIds($user->movies()->with('movie:id,tmdb_id')->get(), 'movie');

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

        $seedShowIds = $this->tmdbIds($showRows->filter(fn ($userShow) => $userShow->status->isActive()), 'show');

        $seeds = $this->seeds($seedShowIds, 'show')
            ->concat($this->seeds($movieIds, 'movie'))
            ->all();

        return collect($this->fetch($seeds))
            ->reject(fn (array $result) => in_array($result['id'], $excluded[$result['type']], true))
            ->unique(fn (array $result) => $result['type'].':'.$result['id'])
            ->sortByDesc('vote_average')
            ->take(self::RESULT_LIMIT)
            ->values()
            ->all();
    }

    /**
     * Distinct TMDB ids off a user's tracked rows, most recently tracked first.
     *
     * @param  Collection<int, Model>  $rows
     * @return Collection<int, string>
     */
    private function tmdbIds(Collection $rows, string $relation): Collection
    {
        return $rows->sortByDesc('created_at')
            ->pluck("{$relation}.tmdb_id")
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, string>  $ids
     * @return Collection<int, array{type: string, id: string}>
     */
    private function seeds(Collection $ids, string $type): Collection
    {
        return $ids->take(self::SEED_LIMIT)->map(fn (string $id) => ['type' => $type, 'id' => $id])->values();
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
        $results = [];
        $misses = [];

        foreach ($seeds as $seed) {
            $cached = Cache::get($this->cacheKey($seed));

            if ($cached === null) {
                $misses[] = $seed;

                continue;
            }

            $results[] = $cached;
        }

        foreach ($this->tmdb->recommendations($misses) as $index => $fetched) {
            Cache::put($this->cacheKey($misses[$index]), $fetched, now()->addMinutes(self::CACHE_TTL_MINUTES));

            $results[] = $fetched;
        }

        return array_merge(...$results ?: [[]]);
    }

    /** @param  array{type: string, id: string}  $seed */
    private function cacheKey(array $seed): string
    {
        return "recommendations:{$seed['type']}:{$seed['id']}";
    }
}
