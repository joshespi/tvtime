<?php

namespace App\Services\Shows;

use App\Models\Episode;
use App\Models\Show;
use App\Services\Concerns\MapsTmdbMetadata;
use App\Services\Concerns\RefreshesCastAndTrailer;
use Illuminate\Support\Facades\Log;

class ShowSyncService
{
    use MapsTmdbMetadata;
    use RefreshesCastAndTrailer;

    public function __construct(private readonly TmdbClient $tmdb) {}

    public function store(string $externalId): Show
    {
        $show = $this->tmdb->show($externalId, strict: true);

        if (! $show) {
            throw ShowNotFoundException::forQuery($externalId);
        }

        $record = Show::updateOrCreate(['tmdb_id' => $externalId], [
            ...$this->storeAttributes($show),
            'first_air_date' => $show['first_air_date'] ?? null,
        ]);

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
        // which only runs once when a show is first tracked. show() still brings
        // external_ids, which fills in imdb_id for shows tracked before it existed.
        $details = $this->tmdb->show($externalId, append: []);

        if (! $details) {
            Log::warning('Skipping episode sync, TMDB fetch failed', ['show_id' => $show->id]);

            return;
        }

        $this->syncEpisodes($show, $this->tmdb->episodes($externalId, $details['number_of_seasons'] ?? null));

        $show->update([
            ...$this->refreshAttributes($details, $show),
            'first_air_date' => $details['first_air_date'] ?? $show->first_air_date,
        ]);
    }

    /** Re-fetch cast/trailer for a show that was tracked before those columns existed. */
    public function refreshCastAndTrailer(Show $show): bool
    {
        return $this->backfillCastAndTrailer($show, fn (string $id) => $this->tmdb->show($id));
    }

    /**
     * @param  array<int, array{season: int, episode_number: int, title: ?string, air_date: ?string, external_id: ?string}>  $episodes
     */
    private function syncEpisodes(Show $show, array $episodes): void
    {
        if ($episodes === []) {
            return;
        }

        // toBase(): Eloquent's upsert() adds updated_at to the update columns,
        // which changes on every run and so forces MariaDB to rewrite every
        // episode of every show daily. Without it an unchanged row is skipped;
        // updated_at just keeps its insert time, and nothing reads it.
        $now = now();

        Episode::query()->toBase()->upsert(
            array_map(fn (array $episode) => [
                'show_id' => $show->id,
                'season' => $episode['season'],
                'episode_number' => $episode['episode_number'],
                'title' => $episode['title'],
                'air_date' => $episode['air_date'],
                'tmdb_id' => $episode['external_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $episodes),
            ['show_id', 'season', 'episode_number'],
            ['title', 'air_date', 'tmdb_id'],
        );
    }
}
