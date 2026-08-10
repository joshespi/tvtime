<?php

namespace Tests\Unit\Services;

use App\Models\Movie;
use App\Services\Movies\MovieNotFoundException;
use App\Services\Movies\MovieSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MovieSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private MovieSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MovieSyncService::class);
    }

    public function test_store_creates_a_movie_from_tmdb(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'poster_path' => '/matrix.jpg',
                'overview' => 'A hacker...',
                'status' => 'Released',
                'vote_average' => 8.7,
                'genres' => [['name' => 'Action']],
                'release_date' => '1999-03-31',
            ]),
        ]);

        $movie = $this->service->store('tmdb', '603');

        $this->assertSame('The Matrix', $movie->title);
        $this->assertSame('603', $movie->tmdb_id);
        $this->assertSame('tmdb', $movie->resolved_source);
    }

    public function test_store_is_idempotent_for_the_same_external_id(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'release_date' => '1999-03-31',
            ]),
        ]);

        $first = $this->service->store('tmdb', '603');
        $second = $this->service->store('tmdb', '603');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Movie::query()->count());
    }

    public function test_store_throws_when_the_provider_has_no_match(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $this->expectException(MovieNotFoundException::class);

        $this->service->store('tmdb', 'missing');
    }

    public function test_refresh_metadata_updates_fields_from_the_originally_resolved_provider(): void
    {
        $movie = Movie::factory()->create([
            'tmdb_id' => '603',
            'resolved_source' => 'tmdb',
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

    public function test_refresh_metadata_does_nothing_without_a_linked_external_id(): void
    {
        $movie = Movie::factory()->create(['tmdb_id' => null, 'trakt_id' => null, 'resolved_source' => 'tmdb']);

        Http::fake();

        $this->service->refreshMetadata($movie);

        Http::assertNothingSent();
    }

    public function test_refresh_metadata_does_nothing_when_the_provider_fetch_fails(): void
    {
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'resolved_source' => 'tmdb', 'overview' => 'stale overview', 'synced_at' => null]);

        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $this->service->refreshMetadata($movie);

        $this->assertSame('stale overview', $movie->fresh()->overview);
        $this->assertNull($movie->fresh()->synced_at);
    }
}
