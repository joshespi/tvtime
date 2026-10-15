<?php

namespace Tests\Unit\Services;

use App\Models\Show;
use App\Services\Shows\ShowNotFoundException;
use App\Services\Shows\ShowSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesTmdb;
use Tests\TestCase;

class ShowSyncServiceTest extends TestCase
{
    use FakesTmdb;
    use RefreshDatabase;

    private ShowSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ShowSyncService::class);
    }

    public function test_store_creates_a_show_and_its_episodes_from_tmdb(): void
    {
        $this->fakeTmdbShow();

        $show = $this->service->store('1396');

        $this->assertSame('Breaking Bad', $show->title);
        $this->assertSame('tt0903747', $show->imdb_id);
        $this->assertSame('1396', $show->tmdb_id);
        $this->assertDatabaseHas('episodes', ['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);
    }

    public function test_store_is_idempotent_for_the_same_external_id(): void
    {
        $this->fakeTmdbShow();

        $first = $this->service->store('1396');
        $second = $this->service->store('1396');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Show::query()->count());
    }

    public function test_store_throws_when_tmdb_has_no_match(): void
    {
        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $this->expectException(ShowNotFoundException::class);

        $this->service->store('missing');
    }

    public function test_refresh_episodes_refetches_from_tmdb(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396']);

        $this->fakeTmdbShow();

        $this->service->refreshEpisodes($show);

        $this->assertDatabaseHas('episodes', ['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $this->assertNotNull($show->fresh()->synced_at);
    }

    public function test_refresh_episodes_does_nothing_without_a_linked_tmdb_id(): void
    {
        $show = Show::factory()->create(['tmdb_id' => null]);

        Http::fake();

        $this->service->refreshEpisodes($show);

        Http::assertNothingSent();
    }

    public function test_refresh_episodes_skips_when_the_tmdb_fetch_fails(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'synced_at' => null]);

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $this->service->refreshEpisodes($show);

        $this->assertNull($show->fresh()->synced_at);
    }
}
