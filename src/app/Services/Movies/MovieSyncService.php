<?php

namespace App\Services\Movies;

use App\Models\Movie;
use App\Services\Shows\TmdbClient;
use App\Services\Shows\TraktClient;
use App\Support\Provider;

/**
 * Movie counterpart to ShowSyncService — same TMDB-primary/Trakt-fallback
 * resolution, but there's no episodes() step since a movie has no sub-items.
 */
class MovieSyncService
{
    public function __construct(
        private readonly TmdbClient $tmdb,
        private readonly TraktClient $trakt,
    ) {}

    public function store(string $source, string $externalId): Movie
    {
        $provider = Provider::from($source);
        $client = $provider->client($this->tmdb, $this->trakt);
        $movie = $client->movie($externalId);

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
            'resolved_source' => $source,
            'synced_at' => now(),
        ];

        return Movie::updateOrCreate([$provider->idColumn() => $externalId], $attributes);
    }

    /**
     * Re-fetch metadata for a movie already in the catalog, using whichever
     * provider originally resolved it.
     */
    public function refreshMetadata(Movie $movie): void
    {
        $provider = Provider::forRecord($movie);
        $client = $provider->client($this->tmdb, $this->trakt);
        $externalId = $provider->externalIdOf($movie);

        if (! $externalId) {
            return;
        }

        $details = $client->movie($externalId);

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
