<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesRatings;
use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Support\GenreFilter;
use App\Support\TrackingStatus;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShowController extends Controller
{
    use SavesRatings;

    public function __construct(private readonly CatalogSearchService $catalog) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        $tracked = $request->user()->shows()
            ->with('show.nextUpcomingEpisode')
            ->get();

        [$tracked, $genre, $genreOptions] = GenreFilter::fromRequest(
            $request, $tracked, fn (UserShow $userShow) => $userShow->show->genres,
        );

        $tracked = WatchlistSort::apply(
            $tracked,
            $sort,
            fn (UserShow $userShow) => $userShow->show->first_air_date,
            fn (UserShow $userShow) => $userShow->show->title,
        );

        // Three shelves, each keeping the chosen sort within itself: what's
        // being watched, what's caught up, and what's deliberately not being
        // followed. The shelf is what says how closely a show is followed, so
        // paused and dropped ones don't sit in a list headed "Tracking".
        [$inactive, $tracked] = $tracked->partition(
            fn (UserShow $userShow) => ! $userShow->status->isActive()
        );

        [$finished, $tracked] = $tracked->partition(
            fn (UserShow $userShow) => $userShow->status === TrackingStatus::Finished
        );

        return view('shows.index', [
            'tracked' => $tracked->values(),
            'finished' => $finished->values(),
            'inactive' => $inactive->values(),
            'sort' => $sort,
            'genre' => $genre,
            'genreOptions' => $genreOptions,
            ...$this->catalog->forRequest($request),
        ]);
    }

    public function show(Request $request, Show $show): View
    {
        // A read, so 404 rather than confirm the show id exists (see trackedShow()).
        $userShow = $request->user()->trackedShow($show) ?? abort(404);

        $episodes = $show->episodesWithWatchState($request->user()->id);

        $seasons = $episodes->groupBy('season');

        // Seasons start folded except the one the user is actually in: the
        // first with an aired episode still unwatched, or the latest season
        // when they're all caught up.
        $openSeason = $seasons->search(fn ($episodes) => $episodes->contains(
            fn (Episode $episode) => ! $episode->watched && $episode->hasAired()
        ));
        $openSeason = $openSeason === false ? $seasons->keys()->last() : $openSeason;

        return view('shows.show', [
            'show' => $show,
            'userShow' => $userShow,
            'seasons' => $seasons,
            'openSeason' => $openSeason,
        ]);
    }

    /**
     * Change how closely a show is followed without losing it — the "stop
     * watching but keep it" path, as opposed to destroy() which forgets it.
     */
    public function updateStatus(Request $request, UserShow $userShow): RedirectResponse
    {
        Gate::authorize('update', $userShow);

        $request->validate([
            'status' => ['required', Rule::enum(TrackingStatus::class)],
        ]);

        $status = $request->enum('status', TrackingStatus::class);

        $userShow->update(['status' => $status]);

        return back()->with('status', "\"{$userShow->show->title}\" is now marked {$status->label()}.");
    }

    public function updateRating(Request $request, UserShow $userShow): RedirectResponse
    {
        return $this->saveRating($request, $userShow);
    }

    public function destroy(UserShow $userShow): RedirectResponse
    {
        Gate::authorize('delete', $userShow);

        $userShow->delete();

        return redirect()->route('shows.index')->with('status', 'Show removed from your list.');
    }
}
