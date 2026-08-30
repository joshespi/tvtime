<?php

namespace App\Services\Movies;

use App\Models\Movie;
use App\Services\Concerns\RefreshesCastAndTrailer;
use App\Services\Shows\TmdbClient;

class MovieSyncService
{
    use RefreshesCastAndTrailer;

    public function __construct(private readonly TmdbClient $tmdb) {}

    public function store(string $externalId): Movie
    {
        $movie = $this->tmdb->movie($externalId);

        if (! $movie) {
            throw MovieNotFoundException::forQuery($externalId);
        }

        $attributes = [
            'title' => $movie['title'],
            'poster_path' => $movie['poster_path'],
            'overview' => $movie['overview'] ?? null,
            'status' => $movie['status'] ?? null,
            'vote_average' => $movie['vote_average'] ?? null,
            'genres' => $movie['genres'] ?? [],
            'release_date' => $movie['release_date'] ?? null,
            'cast' => $movie['cast'] ?? [],
            'trailer_key' => $movie['trailer_key'] ?? null,
            'synced_at' => now(),
        ];

        return Movie::updateOrCreate(['tmdb_id' => $externalId], $attributes);
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
            return;
        }

        $movie->update([
            'synced_at' => now(),
            'overview' => $details['overview'] ?? $movie->overview,
            'status' => $details['status'] ?? $movie->status,
            'vote_average' => $details['vote_average'] ?? $movie->vote_average,
            'genres' => $details['genres'] ?: $movie->genres,
            'release_date' => $details['release_date'] ?? $movie->release_date,
        ]);
    }

    /** Re-fetch cast/trailer for a movie that was tracked before those columns existed. */
    public function refreshCastAndTrailer(Movie $movie): void
    {
        $this->backfillCastAndTrailer($movie, fn (string $id) => $this->tmdb->movie($id));
    }
}
