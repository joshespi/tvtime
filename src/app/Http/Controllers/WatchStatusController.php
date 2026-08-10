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
        $status = WatchStatus::firstOrNew([
            'user_id' => $request->user()->id,
            'episode_id' => $episode->id,
        ]);

        $status->watched_at = $status->exists && $status->watched_at ? null : now();
        $status->save();

        return back();
    }

    /**
     * Marks every episode up through and including the given one as watched,
     * spanning prior seasons — for clearing a gap of earlier unwatched episodes.
     */
    public function markThrough(Request $request, Episode $episode): RedirectResponse
    {
        $episode->markWatchedThrough($request->user()->id);

        return back();
    }
}
