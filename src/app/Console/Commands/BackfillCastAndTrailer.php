<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Show;
use App\Services\Movies\MovieSyncService;
use App\Services\Shows\ShowSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:backfill-cast-trailer')]
#[Description('One-off backfill: fetch cast/trailer_key for shows and movies tracked before those columns existed (2026-08-27)')]
class BackfillCastAndTrailer extends Command
{
    public function handle(ShowSyncService $shows, MovieSyncService $movies): void
    {
        $trackedShows = Show::query()->whereNull('cast')->whereNotNull('tmdb_id')->get();

        foreach ($trackedShows as $show) {
            try {
                $shows->refreshCastAndTrailer($show);
            } catch (Throwable $e) {
                Log::error('Failed to backfill cast/trailer for show', ['show_id' => $show->id, 'message' => $e->getMessage()]);
            }
        }

        $this->info("Backfilled {$trackedShows->count()} show(s).");

        $trackedMovies = Movie::query()->whereNull('cast')->whereNotNull('tmdb_id')->get();

        foreach ($trackedMovies as $movie) {
            try {
                $movies->refreshCastAndTrailer($movie);
            } catch (Throwable $e) {
                Log::error('Failed to backfill cast/trailer for movie', ['movie_id' => $movie->id, 'message' => $e->getMessage()]);
            }
        }

        $this->info("Backfilled {$trackedMovies->count()} movie(s).");
    }
}
