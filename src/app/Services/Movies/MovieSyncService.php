<?php

namespace App\Services\Movies;

use App\Models\Movie;
use App\Services\Concerns\MapsTmdbMetadata;
use App\Services\Concerns\RefreshesCastAndTrailer;
use App\Services\Shows\TmdbClient;
use Illuminate\Support\Facades\Log;

class MovieSyncService
{
    use MapsTmdbMetadata;
    use RefreshesCastAndTrailer;

    public function __construct(private readonly TmdbClient $tmdb) {}

    public function store(string $externalId): Movie
    {
        $movie = $this->tmdb->movie($externalId, strict: true);

        if (! $movie) {
            throw MovieNotFoundException::forQuery($externalId);
        }

        return Movie::updateOrCreate(['tmdb_id' => $externalId], [
            ...$this->storeAttributes($movie),
            'release_date' => $movie['release_date'] ?? null,
        ]);
    }

    /** Re-fetch metadata for a movie already in the catalog. */
    public function refreshMetadata(Movie $movie): void
    {
        if (! $movie->tmdb_id) {
            return;
        }

        // Cast/trailer barely change once a title's out, so this skips that part
        // of the payload — this runs daily for every tracked movie, unlike store()
        // which only runs once when a movie is first tracked.
        $details = $this->tmdb->movie((string) $movie->tmdb_id, append: []);

        if (! $details) {
            Log::warning('Skipping movie metadata refresh, TMDB fetch failed', ['movie_id' => $movie->id]);

            return;
        }

        $movie->update([
            ...$this->refreshAttributes($details, $movie),
            'release_date' => $details['release_date'] ?? $movie->release_date,
        ]);
    }

    /** Re-fetch cast/trailer for a movie that was tracked before those columns existed. */
    public function refreshCastAndTrailer(Movie $movie): bool
    {
        return $this->backfillCastAndTrailer($movie, fn (string $id) => $this->tmdb->movie($id));
    }
}
