<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Shows\ShowSyncService;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShowController extends Controller
{
    public function __construct(
        private readonly CatalogSearchService $catalog,
        private readonly ShowSyncService $shows,
    ) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        $tracked = $request->user()->shows()->with('show')->get();

        $tracked = WatchlistSort::apply(
            $tracked,
            $sort,
            fn (UserShow $userShow) => $userShow->show->first_air_date,
            fn (UserShow $userShow) => $userShow->show->title,
        );

        return view('shows.index', [
            'tracked' => $tracked,
            'sort' => $sort,
            ...$this->catalog->forRequest($request),
        ]);
    }

    public function show(Request $request, Show $show): View
    {
        $userShow = $this->trackedOrFail($request, $show->id);

        $episodes = $show->episodesWithWatchState($request->user()->id);

        $linkQuery = $request->string('link_query')->trim()->toString();

        return view('shows.show', [
            'show' => $show,
            'userShow' => $userShow,
            'seasons' => $episodes->groupBy('season'),
            'linkQuery' => $linkQuery,
            'linkResults' => $linkQuery !== '' ? $this->shows->searchSecondarySource($show, $linkQuery) : [],
            // The provider to cross-check against, named once here rather than
            // re-derived from 'tmdb'/'trakt' string ternaries in the view.
            'secondary' => $this->shows->secondarySource($show),
        ]);
    }

    public function destroy(UserShow $userShow): RedirectResponse
    {
        Gate::authorize('delete', $userShow);

        $userShow->delete();

        return redirect()->route('shows.index')->with('status', 'Show removed from your list.');
    }

    public function linkSource(Request $request, Show $show): RedirectResponse
    {
        $this->trackedOrFail($request, $show->id);

        $validated = $request->validate([
            'source' => 'required|in:tmdb,trakt',
            'external_id' => 'required|string',
        ]);

        $this->shows->linkSecondarySource($show, $validated['source'], $validated['external_id']);

        return redirect()->route('shows.show', $show)->with('status', 'Linked '.ucfirst($validated['source']).'.');
    }

    public function checkConflicts(Request $request, Show $show): RedirectResponse
    {
        $this->trackedOrFail($request, $show->id);

        $conflicts = $this->shows->findAirDateConflicts($show);

        if ($conflicts === []) {
            return redirect()->route('shows.show', $show)->with('status', 'No air-date conflicts found.');
        }

        return redirect()->route('shows.show', $show)->with('conflicts', $conflicts);
    }

    public function resolveConflict(Request $request, Episode $episode): RedirectResponse
    {
        $this->trackedOrFail($request, $episode->show_id);

        $validated = $request->validate([
            'air_date' => 'required|date',
        ]);

        $episode->update(['air_date' => $validated['air_date']]);

        return redirect()->route('shows.show', $episode->show_id)->with('status', "Updated air date for {$episode->code()}.");
    }

    /**
     * The current user's tracking record for a show, 404ing if they don't track
     * it. Every action on this controller is scoped that way, so the rule lives
     * in one place rather than being restated per action.
     */
    private function trackedOrFail(Request $request, int $showId): UserShow
    {
        return $request->user()->shows()->where('show_id', $showId)->firstOrFail();
    }
}
