<?php

namespace App\Services\Shows;

use App\Models\Episode;
use App\Models\Show;
use Illuminate\Support\Facades\Log;

class ShowSyncService
{
    public function __construct(private readonly TmdbClient $tmdb) {}

    public function store(string $externalId): Show
    {
        $show = $this->tmdb->show($externalId);

        if (! $show) {
            throw ShowNotFoundException::forQuery($externalId);
        }

        $attributes = [
            'title' => $show['title'],
            'poster_path' => $show['poster_path'],
            'overview' => $show['overview'] ?? null,
            'status' => $show['status'] ?? null,
            'vote_average' => $show['vote_average'] ?? null,
            'genres' => $show['genres'] ?? [],
            'first_air_date' => $show['first_air_date'] ?? null,
            'cast' => $show['cast'] ?? [],
            'trailer_key' => $show['trailer_key'] ?? null,
            'synced_at' => now(),
        ];

        $record = Show::updateOrCreate(['tmdb_id' => $externalId], $attributes);

        $this->syncEpisodes($record, $this->tmdb->episodes($externalId, $show['number_of_seasons'] ?? null));

        return $record;
    }

    /** Re-fetch episodes for a show already in the catalog. */
    public function refreshEpisodes(Show $show): void
    {
        if (! $show->tmdb_id) {
            return;
        }

        $externalId = (string) $show->tmdb_id;
        // Cast/trailer barely change once a title's out, so this skips that part
        // of the payload — this runs daily for every tracked show, unlike store()
        // which only runs once when a show is first tracked.
        $details = $this->tmdb->show($externalId, append: []);

        if (! $details) {
            Log::warning('Skipping episode sync, TMDB fetch failed', ['show_id' => $show->id]);

            return;
        }

        $this->syncEpisodes($show, $this->tmdb->episodes($externalId, $details['number_of_seasons'] ?? null));

        $show->update([
            'synced_at' => now(),
            'overview' => $details['overview'] ?? $show->overview,
            'status' => $details['status'] ?? $show->status,
            'vote_average' => $details['vote_average'] ?? $show->vote_average,
            'genres' => $details['genres'] ?: $show->genres,
        ]);
    }

    /**
     * @param  array<int, array{season: int, episode_number: int, title: ?string, air_date: ?string, external_id: ?string}>  $episodes
     */
    private function syncEpisodes(Show $show, array $episodes): void
    {
        if ($episodes === []) {
            return;
        }

        Episode::upsert(
            array_map(fn (array $episode) => [
                'show_id' => $show->id,
                'season' => $episode['season'],
                'episode_number' => $episode['episode_number'],
                'title' => $episode['title'],
                'air_date' => $episode['air_date'],
                'tmdb_id' => $episode['external_id'],
            ], $episodes),
            ['show_id', 'season', 'episode_number'],
            ['title', 'air_date', 'tmdb_id'],
        );
    }
}
