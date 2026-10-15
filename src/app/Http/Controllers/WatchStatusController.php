<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TogglesWatched;
use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchStatusController extends Controller
{
    use TogglesWatched;

    public function toggle(Request $request, Episode $episode): RedirectResponse
    {
        $userShow = $this->trackedOrFail($request, $episode);

        // Only looked up when the request doesn't name the state it wants.
        $watched = $this->requestedWatched($request, fn () => $userShow->isWatched($episode));

        $watched ? $userShow->markWatched([$episode->id]) : $userShow->markUnwatched($episode);

        return $this->withUndo(
            $this->backToEpisode($episode),
            "{$userShow->show->title} {$episode->code()}",
            $watched,
            route('episodes.toggle-watched', $episode),
        );
    }

    /**
     * Marks every episode up through and including the given one as watched,
     * spanning prior seasons — for clearing a gap of earlier unwatched episodes.
     */
    public function markThrough(Request $request, Episode $episode): RedirectResponse
    {
        $userShow = $this->trackedOrFail($request, $episode);
        $userShow->markWatched(Episode::query()->upThrough($episode)->pluck('id'));

        return $this->backToEpisode($episode)
            ->with('status', "{$userShow->show->title} marked watched through {$episode->code()}.");
    }

    /**
     * Marks every episode of a season that has already aired as watched. Future
     * episodes are left alone so a currently-airing season doesn't collect
     * watch marks for episodes that haven't aired yet.
     */
    public function markSeason(Request $request, Show $show, int $season): RedirectResponse
    {
        $userShow = $request->user()->trackedShow($show) ?? abort(403);

        $episodeIds = $show->episodes()
            ->where('season', $season)
            ->unwatchedAiredFor($userShow->user_id)
            ->pluck('id');

        $userShow->markWatched($episodeIds);

        return $this->backToAnchor($show->id, "season-{$season}")->with(
            'status',
            $episodeIds->isEmpty()
                ? "{$show->title} season {$season} has nothing left to mark."
                : "{$show->title} season {$season} marked watched.",
        );
    }

    private function backToEpisode(Episode $episode): RedirectResponse
    {
        return $this->backToAnchor($episode->show_id, "episode-{$episode->id}");
    }

    /**
     * Back to where the request came from — and, when that was the show page
     * (whatever its query string), to the given element on it, so marking
     * something deep in a long show doesn't dump the user back at the top.
     */
    private function backToAnchor(int $showId, string $anchor): RedirectResponse
    {
        $previous = strtok(url()->previous(), '#');

        if (parse_url($previous, PHP_URL_PATH) === parse_url(route('shows.show', $showId), PHP_URL_PATH)) {
            return redirect()->to("{$previous}#{$anchor}");
        }

        return back();
    }

    /**
     * Episode has no owning-user column of its own to check against — a watch
     * status belongs to the user, not the episode — so there's no Model for
     * Gate::authorize() to hand a policy. This is the equivalent check: without
     * it, any authenticated user could POST an arbitrary episode id and record
     * a watch (and skew their own stats/streaks) for a show they never tracked.
     */
    private function trackedOrFail(Request $request, Episode $episode): UserShow
    {
        return $request->user()->trackedShow($episode->show) ?? abort(403);
    }
}
