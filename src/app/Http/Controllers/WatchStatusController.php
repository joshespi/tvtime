<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\WatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchStatusController extends Controller
{
    public function toggle(Request $request, Episode $episode): RedirectResponse
    {
        $this->authorizeTracked($request, $episode);

        $status = WatchStatus::firstOrNew([
            'user_id' => $request->user()->id,
            'episode_id' => $episode->id,
        ]);

        $status->watched_at = $status->exists && $status->watched_at ? null : now();
        $status->save();

        $this->refreshFinishedStatus($request, $episode);

        return back();
    }

    /**
     * Marks every episode up through and including the given one as watched,
     * spanning prior seasons — for clearing a gap of earlier unwatched episodes.
     */
    public function markThrough(Request $request, Episode $episode): RedirectResponse
    {
        $this->authorizeTracked($request, $episode);

        $episode->markWatchedThrough($request->user()->id);

        $this->refreshFinishedStatus($request, $episode);

        return back();
    }

    /**
     * Episode has no owning-user column of its own to check against — a watch
     * status belongs to the user, not the episode — so there's no Model for
     * Gate::authorize() to hand a policy. This is the equivalent check: without
     * it, any authenticated user could POST an arbitrary episode id and record
     * a watch (and skew their own stats/streaks) for a show they never tracked.
     */
    private function authorizeTracked(Request $request, Episode $episode): void
    {
        $tracked = $request->user()->shows()->where('show_id', $episode->show_id)->exists();

        abort_unless($tracked, 403);
    }

    /**
     * Catching up on the last aired episode is what moves a show onto the
     * Finished shelf (and un-marking one takes it back off), so every write to
     * a watch status re-evaluates it.
     */
    private function refreshFinishedStatus(Request $request, Episode $episode): void
    {
        $request->user()->shows()
            ->where('show_id', $episode->show_id)
            ->first()
            ?->refreshFinishedStatus();
    }
}
