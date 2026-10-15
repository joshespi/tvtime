<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * The TmdbClient-payload → column mapping shows and movies share. They differ
 * only in the release-date column, which each service adds itself.
 */
trait MapsTmdbMetadata
{
    /**
     * Everything a first store() writes, cast/trailer included.
     *
     * @param  array<string, mixed>  $details  A TmdbClient::show()/movie() payload.
     * @return array<string, mixed>
     */
    protected function storeAttributes(array $details): array
    {
        return [
            'title' => $details['title'],
            'poster_path' => $details['poster_path'] ?? null,
            'overview' => $details['overview'] ?? null,
            'status' => $details['status'] ?? null,
            'vote_average' => $details['vote_average'] ?? null,
            'genres' => $details['genres'] ?? [],
            'cast' => $details['cast'] ?? [],
            'trailer_key' => $details['trailer_key'] ?? null,
            'imdb_id' => $details['imdb_id'] ?? null,
            'synced_at' => now(),
        ];
    }

    /**
     * What a daily refresh overwrites. Cast/trailer are left alone (the refresh
     * fetch doesn't request them), and a missing field keeps the stored value.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function refreshAttributes(array $details, Model $title): array
    {
        return [
            'title' => ($details['title'] ?? '') ?: $title->title,
            'poster_path' => $details['poster_path'] ?? $title->poster_path,
            'overview' => $details['overview'] ?? $title->overview,
            'status' => $details['status'] ?? $title->status,
            'vote_average' => $details['vote_average'] ?? $title->vote_average,
            'genres' => ($details['genres'] ?? []) ?: $title->genres,
            'imdb_id' => $details['imdb_id'] ?? $title->imdb_id,
            'synced_at' => now(),
        ];
    }
}
