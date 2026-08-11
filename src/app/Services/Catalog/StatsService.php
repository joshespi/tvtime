<?php

namespace App\Services\Catalog;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Backs the profile stats page: watch counts, genre "taste", show status
 * breakdown, and longest consecutive-day streak.
 */
class StatsService
{
    /** @return array{episodesWatchedTotal: int, episodesWatchedThisYear: int, moviesWatchedTotal: int, moviesWatchedThisYear: int, showStatusCounts: Collection, topGenres: Collection, longestStreak: int, memberSince: Carbon} */
    public function forUser(User $user): array
    {
        $episodesWatched = $user->watchStatuses()->whereNotNull('watched_at');
        $moviesWatched = $user->movies()->whereNotNull('watched_at');

        $episodeCounts = $this->watchCounts($episodesWatched);
        $movieCounts = $this->watchCounts($moviesWatched);

        // Genre "taste" comes from everything tracked (shows) or actually finished
        // (watched movies) — the movie watchlist doesn't count yet since it's just
        // intent. Only the genres column is selected; overviews and posters stay put.
        $showGenres = $user->shows()->with('show:id,genres')->get()
            ->flatMap(fn ($userShow) => $userShow->show->genres ?? []);
        $movieGenres = $moviesWatched->with('movie:id,genres')->get()
            ->flatMap(fn ($userMovie) => $userMovie->movie->genres ?? []);

        return [
            'episodesWatchedTotal' => $episodeCounts['total'],
            'episodesWatchedThisYear' => $episodeCounts['thisYear'],
            'moviesWatchedTotal' => $movieCounts['total'],
            'moviesWatchedThisYear' => $movieCounts['thisYear'],
            'showStatusCounts' => $user->shows()->toBase()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'topGenres' => $showGenres->concat($movieGenres)->countBy()->sortDesc()->take(5),
            'longestStreak' => $this->longestStreak(
                $this->watchedDays($episodesWatched)->concat($this->watchedDays($moviesWatched))
            ),
            'memberSince' => $user->created_at,
        ];
    }

    /**
     * Lifetime and year-to-date counts in one aggregate query, so a long watch
     * history is never hydrated into models just to be counted.
     *
     * @return array{total: int, thisYear: int}
     */
    private function watchCounts(Relation $watched): array
    {
        $counts = $watched->clone()->toBase()->selectRaw(
            'count(*) as total, count(case when watched_at >= ? then 1 end) as this_year',
            [now()->startOfYear()],
        )->first();

        return ['total' => (int) $counts->total, 'thisYear' => (int) $counts->this_year];
    }

    /**
     * The distinct calendar days with activity, as Y-m-d strings. Distinct days
     * rather than rows: the streak only asks whether a day had anything at all,
     * and an active user has far more watch rows than watch days.
     *
     * @return Collection<int, string>
     */
    private function watchedDays(Relation $watched): Collection
    {
        return $watched->clone()->toBase()
            ->distinct()
            ->selectRaw('date(watched_at) as day')
            ->pluck('day');
    }

    /**
     * Longest run of consecutive calendar days with at least one episode or
     * movie marked watched, merging both watched-at sources.
     *
     * @param  Collection<int, string>  $days
     */
    private function longestStreak(Collection $days): int
    {
        // Compared as epoch day numbers: two days are consecutive iff they differ
        // by one, so no date object has to be built to walk the run.
        $epochDays = $days->map(fn (string $day) => (int) floor(strtotime($day.' UTC') / 86400))
            ->unique()
            ->sort()
            ->values();

        $longest = 0;
        $current = 0;
        $previous = null;

        foreach ($epochDays as $day) {
            $current = ($previous !== null && $day - $previous === 1) ? $current + 1 : 1;
            $longest = max($longest, $current);
            $previous = $day;
        }

        return $longest;
    }
}
