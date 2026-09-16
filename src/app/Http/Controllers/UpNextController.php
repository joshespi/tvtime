<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Catalog\RecommendationService;
use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UpNextController extends Controller
{
    /**
     * How many Airing Soon cards the dashboard shows before linking out to the
     * full list — one row at the grid's widest breakpoint (xl:grid-cols-3 on
     * <x-card-grid>), so mobile isn't stuck scrolling past everything upcoming.
     */
    private const AIRING_SOON_PREVIEW = 3;

    public function __construct(
        private readonly CatalogSearchService $catalog,
        private readonly RecommendationService $recommendations,
    ) {}

    /**
     * Earliest unwatched aired episode per actively-watched show, plus the full
     * movie watchlist (movies have no "next episode", so unwatched = up next).
     */
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;

        // Both episode selections are eager-loaded in one pass: `episodes` is
        // narrowed to the single next unwatched-aired one, and the next upcoming
        // one comes from its own relation so the two can't overwrite each other.
        $watchingShows = $this->activeShows($request)
            ->with([
                'show.episodes' => fn ($query) => $query
                    ->unwatchedAiredFor($userId)
                    ->orderBy('season')
                    ->orderBy('episode_number')
                    ->limit(1),
                'show.nextUpcomingEpisode',
            ])
            ->get();

        $upNext = $this->pairWithEpisode($watchingShows, fn (UserShow $userShow) => $userShow->show->episodes->first());

        $airingSoon = $this->airingSoonFor($watchingShows);

        $movieWatchlist = $request->user()->movies()
            ->inWatchlist()
            ->with('movie')
            ->withCount('watches')
            ->orderByDesc('created_at')
            ->get();

        return view('up-next', [
            'upNext' => $upNext,
            'airingSoon' => $airingSoon->take(self::AIRING_SOON_PREVIEW),
            'airingSoonHasMore' => $airingSoon->count() > self::AIRING_SOON_PREVIEW,
            'movieWatchlist' => $movieWatchlist,
            'recommendations' => $this->recommendations->forUser($request->user()),
            ...$this->catalog->forRequest($request),
        ]);
    }

    /**
     * The full Airing Soon list, for the "see all" drill-in from the dashboard's
     * trimmed preview.
     */
    public function airingSoon(Request $request): View
    {
        $watchingShows = $this->activeShows($request)
            ->with('show.nextUpcomingEpisode')
            ->get();

        return view('airing-soon', [
            'airingSoon' => $this->airingSoonFor($watchingShows),
        ]);
    }

    /**
     * The current user's actively-watched shows, unloaded — callers attach
     * whichever `show` relations their view needs before executing the query.
     */
    private function activeShows(Request $request): HasMany
    {
        return $request->user()->shows()->active();
    }

    /**
     * @param  Collection<int, UserShow>  $watchingShows
     * @return Collection<int, array{show: Show, episode: Episode}>
     */
    private function airingSoonFor(Collection $watchingShows): Collection
    {
        return $this->pairWithEpisode($watchingShows, fn (UserShow $userShow) => $userShow->show->nextUpcomingEpisode)
            ->sortBy(fn (array $entry) => $entry['episode']->air_date)
            ->values();
    }

    /**
     * Shows paired with one already-loaded episode, dropping shows that have none.
     *
     * @param  Collection<int, UserShow>  $watchingShows
     * @return Collection<int, array{show: Show, episode: Episode}>
     */
    private function pairWithEpisode(Collection $watchingShows, Closure $episode): Collection
    {
        return $watchingShows
            ->map(fn (UserShow $userShow) => ['show' => $userShow->show, 'episode' => $episode($userShow)])
            ->filter(fn (array $entry) => $entry['episode'] !== null)
            ->values();
    }
}
