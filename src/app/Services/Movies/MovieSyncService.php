<?php

namespace App\Services\Movies;

use App\Models\Movie;
use App\Services\Shows\TmdbClient;

class MovieSyncService
{
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

        $details = $this->tmdb->movie((string) $movie->tmdb_id);

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
}
