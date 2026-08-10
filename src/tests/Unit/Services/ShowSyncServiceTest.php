<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\Show;
use App\Services\Shows\ShowNotFoundException;
use App\Services\Shows\ShowSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShowSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShowSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ShowSyncService::class);
    }

    private function fakeTmdbShow(int $seasons = 1): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Pilot', 'air_date' => '2008-01-20', 'id' => 111],
                ],
            ]),
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'poster_path' => '/poster.jpg',
                'number_of_seasons' => $seasons,
                'overview' => 'A chemistry teacher...',
                'status' => 'Ended',
                'vote_average' => 8.9,
                'genres' => [['name' => 'Drama']],
                'first_air_date' => '2008-01-20',
            ]),
        ]);
    }

    public function test_store_creates_a_show_and_its_episodes_from_tmdb(): void
    {
        $this->fakeTmdbShow();

        $show = $this->service->store('tmdb', '1396');

        $this->assertSame('Breaking Bad', $show->title);
        $this->assertSame('1396', $show->tmdb_id);
        $this->assertSame('tmdb', $show->resolved_source);
        $this->assertDatabaseHas('episodes', ['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);
    }

    public function test_store_is_idempotent_for_the_same_external_id(): void
    {
        $this->fakeTmdbShow();

        $first = $this->service->store('tmdb', '1396');
        $second = $this->service->store('tmdb', '1396');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Show::query()->count());
    }

    public function test_store_throws_when_the_provider_has_no_match(): void
    {
        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $this->expectException(ShowNotFoundException::class);

        $this->service->store('tmdb', 'missing');
    }

    public function test_refresh_episodes_uses_the_originally_resolved_provider(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => null, 'resolved_source' => 'tmdb']);

        $this->fakeTmdbShow();

        $this->service->refreshEpisodes($show);

        $this->assertDatabaseHas('episodes', ['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $this->assertNotNull($show->fresh()->synced_at);
    }

    public function test_refresh_episodes_does_nothing_without_a_linked_external_id(): void
    {
        $show = Show::factory()->create(['tmdb_id' => null, 'trakt_id' => null, 'resolved_source' => 'tmdb']);

        Http::fake();

        $this->service->refreshEpisodes($show);

        Http::assertNothingSent();
    }

    public function test_refresh_episodes_skips_when_the_provider_fetch_fails(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => null, 'resolved_source' => 'tmdb', 'synced_at' => null]);

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $this->service->refreshEpisodes($show);

        $this->assertNull($show->fresh()->synced_at);
    }

    public function test_search_secondary_source_returns_empty_without_a_resolved_source(): void
    {
        $show = Show::factory()->create(['resolved_source' => null]);

        $this->assertSame([], $this->service->searchSecondarySource($show, 'query'));
    }

    public function test_search_secondary_source_searches_the_other_provider(): void
    {
        $show = Show::factory()->create(['resolved_source' => 'tmdb']);

        Http::fake([
            'api.trakt.tv/search/movie,show*' => Http::response([
                ['type' => 'show', 'show' => ['ids' => ['slug' => 'breaking-bad'], 'title' => 'Breaking Bad', 'year' => 2008]],
                ['type' => 'movie', 'movie' => ['ids' => ['slug' => 'a-movie'], 'title' => 'A Movie', 'year' => 2020]],
            ]),
        ]);

        $results = $this->service->searchSecondarySource($show, 'breaking');

        $this->assertCount(1, $results);
        $this->assertSame('show', $results[0]['type']);
    }

    public function test_link_secondary_source_sets_the_id_column(): void
    {
        $show = Show::factory()->create(['resolved_source' => 'tmdb', 'trakt_id' => null]);

        $this->service->linkSecondarySource($show, 'trakt', 'breaking-bad');

        $this->assertSame('breaking-bad', $show->fresh()->trakt_id);
    }

    public function test_link_secondary_source_ignores_the_primary_source(): void
    {
        $show = Show::factory()->create(['resolved_source' => 'tmdb', 'tmdb_id' => '1396']);

        $this->service->linkSecondarySource($show, 'tmdb', '9999');

        $this->assertEquals(1396, $show->fresh()->tmdb_id);
    }

    public function test_find_air_date_conflicts_returns_empty_without_both_ids_linked(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => null]);

        $this->assertSame([], $this->service->findAirDateConflicts($show));
    }

    public function test_find_air_date_conflicts_detects_a_mismatched_air_date(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => 'breaking-bad', 'resolved_source' => 'tmdb']);
        $episode = Episode::factory()->create([
            'show_id' => $show->id,
            'season' => 1,
            'episode_number' => 1,
            'air_date' => '2008-01-20',
        ]);

        Http::fake([
            'api.trakt.tv/shows/breaking-bad/seasons*' => Http::response([
                ['number' => 1, 'episodes' => [
                    ['number' => 1, 'title' => 'Pilot', 'first_aired' => '2008-01-21T00:00:00.000Z', 'ids' => ['trakt' => 111]],
                ]],
            ]),
            'api.trakt.tv/shows/breaking-bad*' => Http::response([
                'ids' => ['slug' => 'breaking-bad'],
                'title' => 'Breaking Bad',
                'number_of_seasons' => 1,
            ]),
        ]);

        $conflicts = $this->service->findAirDateConflicts($show);

        $this->assertCount(1, $conflicts);
        $this->assertSame($episode->id, $conflicts[0]['episode_id']);
        $this->assertSame('2008-01-20', $conflicts[0]['current_date']);
        $this->assertSame('2008-01-21', $conflicts[0]['other_date']);
        $this->assertSame('trakt', $conflicts[0]['other_source']);
    }

    public function test_find_air_date_conflicts_ignores_matching_air_dates(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => 'breaking-bad', 'resolved_source' => 'tmdb']);
        Episode::factory()->create([
            'show_id' => $show->id,
            'season' => 1,
            'episode_number' => 1,
            'air_date' => '2008-01-20',
        ]);

        Http::fake([
            'api.trakt.tv/shows/breaking-bad/seasons*' => Http::response([
                ['number' => 1, 'episodes' => [
                    ['number' => 1, 'title' => 'Pilot', 'first_aired' => '2008-01-20T00:00:00.000Z', 'ids' => ['trakt' => 111]],
                ]],
            ]),
            'api.trakt.tv/shows/breaking-bad*' => Http::response([
                'ids' => ['slug' => 'breaking-bad'],
                'title' => 'Breaking Bad',
                'number_of_seasons' => 1,
            ]),
        ]);

        $this->assertSame([], $this->service->findAirDateConflicts($show));
    }
}
