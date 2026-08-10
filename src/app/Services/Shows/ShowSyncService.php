<?php

namespace App\Services\Shows;

use App\Models\Episode;
use App\Models\Show;
use App\Support\Provider;
use Illuminate\Support\Facades\Log;

/**
 * TMDB is primary, Trakt is the fallback. Which source resolved a show is
 * stored on the row so refreshes keep using the same provider.
 */
class ShowSyncService
{
    public function __construct(
        private readonly TmdbClient $tmdb,
        private readonly TraktClient $trakt,
    ) {}

    public function store(string $source, string $externalId): Show
    {
        $provider = Provider::from($source);
        $client = $provider->client($this->tmdb, $this->trakt);
        $show = $client->show($externalId);

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
            'resolved_source' => $source,
            'synced_at' => now(),
        ];

        $record = Show::updateOrCreate(
            [$provider->idColumn() => $externalId],
            $attributes,
        );

        $this->syncEpisodes($record, $provider, $client->episodes($externalId, $show['number_of_seasons'] ?? null));

        return $record;
    }

    /**
     * Re-fetch episodes for a show already in the catalog, using whichever
     * provider originally resolved it.
     */
    public function refreshEpisodes(Show $show): void
    {
        $provider = Provider::forRecord($show);
        $client = $provider->client($this->tmdb, $this->trakt);
        $externalId = $provider->externalIdOf($show);

        if (! $externalId) {
            return;
        }

        $details = $client->show($externalId);

        if (! $details) {
            Log::warning('Skipping episode sync, provider fetch failed', ['show_id' => $show->id, 'source' => $provider->value]);

            return;
        }

        $this->syncEpisodes($show, $provider, $client->episodes($externalId, $details['number_of_seasons'] ?? null));

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
    private function syncEpisodes(Show $show, Provider $provider, array $episodes): void
    {
        if ($episodes === []) {
            return;
        }

        $idColumn = $provider->idColumn();

        Episode::upsert(
            array_map(fn (array $episode) => [
                'show_id' => $show->id,
                'season' => $episode['season'],
                'episode_number' => $episode['episode_number'],
                'title' => $episode['title'],
                'air_date' => $episode['air_date'],
                $idColumn => $episode['external_id'],
            ], $episodes),
            ['show_id', 'season', 'episode_number'],
            ['title', 'air_date', $idColumn],
        );
    }

    /**
     * Search the provider that *didn't* resolve this show, so its id can be
     * linked for cross-checking (see findAirDateConflicts()).
     *
     * @return array<int, array{type: string, id: string, title: string, poster_path: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>
     */
    public function searchSecondarySource(Show $show, string $query): array
    {
        $secondary = $this->secondarySource($show);

        if (! $secondary) {
            return [];
        }

        return collect($secondary->client($this->tmdb, $this->trakt)->searchMulti($query))
            ->filter(fn (array $result) => $result['type'] === 'show')
            ->values()
            ->all();
    }

    /**
     * Link the secondary provider's id for a show, so its episode air dates
     * can be cross-checked against the source that originally resolved it.
     */
    public function linkSecondarySource(Show $show, string $source, string $externalId): void
    {
        if ($source === $show->resolved_source) {
            return;
        }

        $show->update([Provider::from($source)->idColumn() => $externalId]);
    }

    /**
     * Diff episode air dates between the resolved source and the linked
     * secondary source. Returns primitive arrays (not models) so they can be
     * session-flashed.
     *
     * @return array<int, array{episode_id: int, code: string, current_date: string, current_source: string, other_date: string, other_source: string}>
     */
    public function findAirDateConflicts(Show $show): array
    {
        if (! $show->tmdb_id || ! $show->trakt_id) {
            return [];
        }

        $secondary = $this->secondarySource($show);

        if (! $secondary) {
            return [];
        }

        $client = $secondary->client($this->tmdb, $this->trakt);
        $externalId = $secondary->externalIdOf($show);
        $details = $client->show($externalId);

        if (! $details) {
            return [];
        }

        $secondaryEpisodes = collect($client->episodes($externalId, $details['number_of_seasons'] ?? null))
            ->keyBy(fn (array $episode) => "{$episode['season']}:{$episode['episode_number']}");

        $conflicts = [];

        foreach ($show->episodes as $episode) {
            $match = $secondaryEpisodes->get("{$episode->season}:{$episode->episode_number}");

            if (! $match || ! $match['air_date'] || ! $episode->air_date) {
                continue;
            }

            if ($episode->air_date->format('Y-m-d') === $match['air_date']) {
                continue;
            }

            $conflicts[] = [
                'episode_id' => $episode->id,
                'code' => $episode->code(),
                'current_date' => $episode->air_date->format('Y-m-d'),
                'current_source' => $show->resolved_source,
                'other_date' => $match['air_date'],
                'other_source' => $secondary->value,
            ];
        }

        return $conflicts;
    }

    /**
     * The provider a show did *not* come from — the one worth cross-checking
     * against. Public so the show page can offer it by name rather than
     * re-deriving the 'tmdb'/'trakt' pairing in Blade.
     */
    public function secondarySource(Show $show): ?Provider
    {
        return Provider::fromNullable($show->resolved_source)?->other();
    }
}
