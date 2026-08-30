<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * The cast/trailer backfill both sync services need. Shows and movies differ
 * only in which TmdbClient method fetches the detail payload, so the guard,
 * the failure log and the write live here once — see
 * App\Console\Commands\BackfillCastAndTrailer for why this exists at all.
 */
trait RefreshesCastAndTrailer
{
    /**
     * Re-fetch cast/trailer for a title tracked before those columns existed.
     *
     * @param  callable(string): ?array  $fetch  Detail fetch for this title type, with the default append.
     */
    protected function backfillCastAndTrailer(Model $title, callable $fetch): void
    {
        if (! $title->tmdb_id) {
            return;
        }

        $details = $fetch((string) $title->tmdb_id);

        if (! $details) {
            Log::warning('Skipping cast/trailer backfill, TMDB fetch failed', [
                'model' => $title::class,
                'id' => $title->id,
            ]);

            return;
        }

        $title->update([
            'cast' => $details['cast'] ?? [],
            'trailer_key' => $details['trailer_key'] ?? null,
        ]);
    }
}
