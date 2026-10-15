<?php

namespace Tests\Unit\Services;

use App\Models\Movie;
use App\Services\Movies\MovieNotFoundException;
use App\Services\Movies\MovieSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesTmdb;
use Tests\TestCase;

class MovieSyncServiceTest extends TestCase
{
    use FakesTmdb;
    use RefreshDatabase;

    private MovieSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MovieSyncService::class);
    }

    public function test_store_creates_a_movie_from_tmdb(): void
    {
        $this->fakeTmdbMovie();

        $movie = $this->service->store('603');

        $this->assertSame('The Matrix', $movie->title);
        $this->assertSame('tt0133093', $movie->imdb_id);
        $this->assertSame('603', $movie->tmdb_id);
    }

    public function test_store_is_idempotent_for_the_same_external_id(): void
    {
        $this->fakeTmdbMovie();

        $first = $this->service->store('603');
        $second = $this->service->store('603');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Movie::query()->count());
    }

    public function test_store_throws_when_tmdb_has_no_match(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $this->expectException(MovieNotFoundException::class);

        $this->service->store('missing');
    }

    public function test_refresh_metadata_updates_fields_from_tmdb(): void
    {
        $movie = Movie::factory()->create([
            'tmdb_id' => '603',
            'overview' => 'stale overview',
            'vote_average' => 1.0,
        ]);

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'fresh overview',
                'vote_average' => 8.7,
                'release_date' => '1999-03-31',
            ]),
        ]);

        $this->service->refreshMetadata($movie);

        $movie->refresh();
        $this->assertSame('fresh overview', $movie->overview);
        $this->assertSame(8.7, $movie->vote_average);
        $this->assertNotNull($movie->synced_at);
    }

    public function test_refresh_metadata_does_nothing_without_a_linked_tmdb_id(): void
    {
        $movie = Movie::factory()->create(['tmdb_id' => null]);

        Http::fake();

        $this->service->refreshMetadata($movie);

        Http::assertNothingSent();
    }

    public function test_refresh_metadata_does_nothing_when_the_tmdb_fetch_fails(): void
    {
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'overview' => 'stale overview', 'synced_at' => null]);

        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $this->service->refreshMetadata($movie);

        $this->assertSame('stale overview', $movie->fresh()->overview);
        $this->assertNull($movie->fresh()->synced_at);
    }
}
