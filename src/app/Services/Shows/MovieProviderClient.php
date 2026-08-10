<?php

namespace App\Services\Shows;

/**
 * Movie counterpart to EpisodeProviderClient. Lives alongside it since
 * TmdbClient/TraktClient serve both — only the resource paths differ.
 */
interface MovieProviderClient
{
    /**
     * @return array{id: string, title: string, poster_path: ?string, overview: ?string, status: ?string, vote_average: ?float, genres: array<int, string>, release_date: ?string}|null
     */
    public function movie(string $id): ?array;
}
