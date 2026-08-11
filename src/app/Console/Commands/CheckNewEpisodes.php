<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Show;
use App\Notifications\NewEpisodeAired;
use App\Services\Shows\ShowSyncService;
use App\Support\DedupedNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:check-new-episodes')]
#[Description('Refresh episode data for every tracked show and notify users whose episodes just aired')]
class CheckNewEpisodes extends Command
{
    public function handle(ShowSyncService $shows): void
    {
        // Only shows someone is still following: a paused or dropped show
        // costs a TMDB call per run to refresh data nobody is being notified
        // about. Resuming one picks its new episodes up on the next run.
        //
        // with('user') here rather than a second query below: these are exactly
        // the rows the notification loop needs to address.
        $tracked = Show::query()
            ->whereHas('userShows', fn ($query) => $query->active())
            ->with(['userShows' => fn ($query) => $query->active()->with('user')])
            ->get()
            ->keyBy('id');

        foreach ($tracked as $show) {
            try {
                $shows->refreshEpisodes($show);
            } catch (Throwable $e) {
                Log::error('Failed to refresh episodes for show', ['show_id' => $show->id, 'message' => $e->getMessage()]);

                continue;
            }

            // A newly aired episode un-finishes the show for anyone who had
            // caught up, so it leaves the Finished shelf the day it returns.
            foreach ($show->userShows as $userShow) {
                $userShow->refreshFinishedStatus();
            }
        }

        $this->info("Refreshed episode data for {$tracked->count()} show(s).");

        // with('show') because NewEpisodeAired is queued and dereferences
        // $episode->show when the worker renders it — without this, every job
        // re-fetches the same show row.
        $newlyAiredByShow = Episode::query()
            ->with('show')
            ->whereIn('show_id', $tracked->pluck('id'))
            ->airingOn(today())
            ->get()
            ->groupBy('show_id');

        $alreadySent = DedupedNotifier::alreadySent($newlyAiredByShow->flatten());

        $notified = 0;

        foreach ($newlyAiredByShow as $showId => $newlyAired) {
            foreach ($newlyAired as $episode) {
                // Already loaded above — $newlyAiredByShow is scoped to $tracked.
                foreach ($tracked[$showId]->userShows as $userShow) {
                    if (DedupedNotifier::notifyOnce($alreadySent, $userShow->user, new NewEpisodeAired($episode), $episode)) {
                        $notified++;
                    }
                }
            }
        }

        $this->info("Sent {$notified} notification(s).");
    }
}
