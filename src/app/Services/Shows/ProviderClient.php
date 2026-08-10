<?php

namespace App\Services\Shows;

/**
 * Everything a catalog provider must do. TmdbClient and TraktClient are
 * interchangeable through this, which is what Provider::client() hands back —
 * so call sites type-check against the contract rather than against a union of
 * the two concrete classes.
 */
interface ProviderClient extends EpisodeProviderClient, MovieProviderClient
{
    /**
     * Shows and movies in one result set, each tagged with a 'type' of
     * 'show' or 'movie'.
     *
     * @return array<int, array{id: string, type: string, title: string, poster_path: ?string, overview: ?string, year: ?string}>
     */
    public function searchMulti(string $query): array;
}
