<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Support\TrackingStatus;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShowController extends Controller
{
    public function __construct(private readonly CatalogSearchService $catalog) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        $tracked = $request->user()->shows()
            ->with('show.nextUpcomingEpisode')
            ->get();

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
            ...$this->catalog->forRequest($request),
        ]);
    }

    public function show(Request $request, Show $show): View
    {
        $userShow = $this->trackedOrFail($request, $show->id);

        $episodes = $show->episodesWithWatchState($request->user()->id);

        return view('shows.show', [
            'show' => $show,
            'userShow' => $userShow,
            'seasons' => $episodes->groupBy('season'),
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

    public function destroy(UserShow $userShow): RedirectResponse
    {
        Gate::authorize('delete', $userShow);

        $userShow->delete();

        return redirect()->route('shows.index')->with('status', 'Show removed from your list.');
    }

    /**
     * The current user's tracking record for a show, 404ing if they don't track
     * it. Every action on this controller is scoped that way, so the rule lives
     * in one place rather than being restated per action.
     *
     * with('show') because the status picker labels itself from the show title.
     */
    private function trackedOrFail(Request $request, int $showId): UserShow
    {
        return $request->user()->shows()->with('show')->where('show_id', $showId)->firstOrFail();
    }
}
