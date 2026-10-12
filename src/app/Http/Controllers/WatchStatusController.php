<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
use App\Models\WatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchStatusController extends Controller
{
    /**
     * Flips the watched state, unless the request names the state it wants
     * (`watched=1|0`). The forms always name it: a double-tap or a retried
     * request then lands on the same state instead of cancelling itself out,
     * and the Undo button in the status banner can say exactly what to restore.
     */
    public function toggle(Request $request, Episode $episode): RedirectResponse
    {
        $userShow = $this->trackedOrFail($request, $episode);

        $request->validate(['watched' => ['nullable', 'boolean']]);

        $status = WatchStatus::firstOrNew([
            'user_id' => $request->user()->id,
            'episode_id' => $episode->id,
        ]);

        $watched = $request->has('watched')
            ? $request->boolean('watched')
            : ! ($status->exists && $status->watched_at);

        $status->watched_at = $watched ? now() : null;
        $status->save();

        $userShow->refreshFinishedStatus();

        return $this->backToEpisode($episode)
            ->with('status', "{$userShow->show->title} {$episode->code()} marked ".($watched ? 'watched' : 'unwatched').'.')
            ->with('undo', ['url' => route('episodes.toggle-watched', $episode), 'watched' => ! $watched]);
    }

    /**
     * Marks every episode up through and including the given one as watched,
     * spanning prior seasons — for clearing a gap of earlier unwatched episodes.
     */
    public function markThrough(Request $request, Episode $episode): RedirectResponse
    {
        $userShow = $this->trackedOrFail($request, $episode);

        $episode->markWatchedThrough($request->user()->id);

        $userShow->refreshFinishedStatus();

        return $this->backToEpisode($episode);
    }

    /**
     * Marks every episode of a season that has already aired as watched. Future
     * episodes are left alone so a currently-airing season doesn't collect
     * watch marks for episodes that haven't aired yet.
     */
    public function markSeason(Request $request, Show $show, int $season): RedirectResponse
    {
        $userId = $request->user()->id;
        $userShow = $request->user()->shows()->where('show_id', $show->id)->firstOrFail();

        $episodeIds = $show->episodes()
            ->where('season', $season)
            ->unwatchedAiredFor($userId)
            ->pluck('id');

        WatchStatus::markWatched($userId, $episodeIds);

        $userShow->refreshFinishedStatus();

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
        return $request->user()->shows()->with('show')->where('show_id', $episode->show_id)->first() ?? abort(403);
    }
}
