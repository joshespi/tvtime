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
#[Description('One-off backfill: fetch cast/trailer_key/imdb_id for shows and movies tracked before those columns existed')]
class BackfillCastAndTrailer extends Command
{
    public function handle(ShowSyncService $shows, MovieSyncService $movies): void
    {
        foreach (['show' => [Show::class, $shows], 'movie' => [Movie::class, $movies]] as $type => [$model, $sync]) {
            // imdb_id arrived after cast, so a title can have one without the other.
            $titles = $model::query()
                ->where(fn ($query) => $query->whereNull('cast')->orWhereNull('imdb_id'))
                ->whereNotNull('tmdb_id')
                ->get();

            $done = 0;

            foreach ($titles as $title) {
                try {
                    $done += (int) $sync->refreshCastAndTrailer($title);
                } catch (Throwable $e) {
                    Log::error("Failed to backfill TMDB details for {$type}", ["{$type}_id" => $title->id, 'message' => $e->getMessage()]);
                }
            }

            $this->info("Backfilled {$done} of {$titles->count()} {$type}(s).");
        }
    }
}
