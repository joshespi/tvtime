<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Show;
use App\Models\UserShow;
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
        $tracked = Show::query()->whereHas('userShows')->get()->keyBy('id');

        foreach ($tracked as $show) {
            try {
                $shows->refreshEpisodes($show);
            } catch (Throwable $e) {
                Log::error('Failed to refresh episodes for show', ['show_id' => $show->id, 'message' => $e->getMessage()]);
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

        // One grouped query for every show's watchers, rather than one per show
        // inside the loop below.
        $watchingByShow = UserShow::query()
            ->whereIn('show_id', $newlyAiredByShow->keys())
            ->where('status', 'watching')
            ->with('user')
            ->get()
            ->groupBy('show_id');

        $notified = 0;

        foreach ($newlyAiredByShow as $showId => $newlyAired) {
            foreach ($newlyAired as $episode) {
                foreach ($watchingByShow->get($showId, collect()) as $userShow) {
                    if (DedupedNotifier::notifyOnce($alreadySent, $userShow->user, new NewEpisodeAired($episode), $episode)) {
                        $notified++;
                    }
                }
            }
        }

        $this->info("Sent {$notified} notification(s).");
    }
}
