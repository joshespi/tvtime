<?php

namespace App\Services\Shows;

/**
 * Common contract for TMDB/Trakt so ShowSyncService can fall back from one
 * to the other without caring which one it's talking to.
 */
interface EpisodeProviderClient
{
    /**
     * @return array{id: string, title: string, poster_path: ?string, overview: ?string, status: ?string, vote_average: ?float, genres: array<int, string>, first_air_date: ?string}|null
     */
    public function show(string $id): ?array;

    /**
     * Flattened across all seasons, oldest first.
     *
     * $numberOfSeasons is a hint that lets an implementation fan out its
     * per-season requests concurrently; implementations that don't need it
     * (because their API returns every season in one response) ignore it.
     *
     * @return array<int, array{season: int, episode_number: int, title: ?string, air_date: ?string, external_id: ?string}>
     */
    public function episodes(string $id, ?int $numberOfSeasons = null): array;
}
