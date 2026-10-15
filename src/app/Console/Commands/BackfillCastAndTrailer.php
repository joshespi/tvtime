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
        foreach (['show' => [Show::class, $shows], 'movie' => [Movie::class, $movies]] as $type => [$model, $sync]) {
            $titles = $model::query()->whereNull('cast')->whereNotNull('tmdb_id')->get();

            $done = 0;

            foreach ($titles as $title) {
                try {
                    $done += (int) $sync->refreshCastAndTrailer($title);
                } catch (Throwable $e) {
                    Log::error("Failed to backfill cast/trailer for {$type}", ["{$type}_id" => $title->id, 'message' => $e->getMessage()]);
                }
            }

            $this->info("Backfilled {$done} of {$titles->count()} {$type}(s).");
        }
    }
}
