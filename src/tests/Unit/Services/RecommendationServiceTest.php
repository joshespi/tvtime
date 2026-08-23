<?php

namespace Tests\Unit\Services;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Services\Catalog\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $this->service = app(RecommendationService::class);
    }

    public function test_returns_recommendations_seeded_from_a_tracked_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        Http::fake([
            'api.themoviedb.org/3/tv/1396/recommendations*' => Http::response([
                'results' => [
                    ['id' => 2, 'name' => 'Better Call Saul', 'first_air_date' => '2015-02-08', 'poster_path' => '/b.jpg', 'overview' => 'o', 'vote_average' => 8.5],
                ],
            ]),
        ]);

        $results = $this->service->forUser($user);

        $this->assertCount(1, $results);
        $this->assertSame('Better Call Saul', $results[0]['title']);
        $this->assertSame('show', $results[0]['type']);
    }

    public function test_dropped_shows_neither_seed_nor_appear_in_recommendations(): void
    {
        $user = User::factory()->create();
        $dropped = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->dropped()->create(['user_id' => $user->id, 'show_id' => $dropped->id]);

        Http::fake(['api.themoviedb.org/*' => Http::response(['results' => []])]);

        $this->assertSame([], $this->service->forUser($user));
        Http::assertNothingSent();
    }

    public function test_excludes_shows_the_user_already_tracks(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $alreadyTracked = Show::factory()->create(['tmdb_id' => '2']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $alreadyTracked->id]);

        Http::fake([
            'api.themoviedb.org/3/tv/1396/recommendations*' => Http::response([
                'results' => [
                    ['id' => 2, 'name' => 'Already Tracked', 'first_air_date' => '2015-02-08', 'poster_path' => null, 'overview' => 'o', 'vote_average' => 8.5],
                ],
            ]),
            // Unmatched patterns in array-form Http::fake() fall through to a real
            // request otherwise — this show has a second tracked-show seed (tmdb_id 2).
            'api.themoviedb.org/*' => Http::response(['results' => []]),
        ]);

        $results = $this->service->forUser($user);

        $this->assertCount(0, $results);
    }

    public function test_includes_recommendations_seeded_from_a_watchlisted_movie(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603']);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        Http::fake([
            'api.themoviedb.org/3/movie/603/recommendations*' => Http::response([
                'results' => [
                    ['id' => 604, 'title' => 'The Matrix Reloaded', 'release_date' => '2003-05-15', 'poster_path' => null, 'overview' => 'o', 'vote_average' => 7.2],
                ],
            ]),
        ]);

        $results = $this->service->forUser($user);

        $this->assertCount(1, $results);
        $this->assertSame('movie', $results[0]['type']);
    }

    public function test_excludes_recommendations_the_user_dismissed_as_not_interested(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $user->dismissedRecommendations()->create(['type' => 'show', 'tmdb_id' => '2']);

        Http::fake([
            'api.themoviedb.org/3/tv/1396/recommendations*' => Http::response([
                'results' => [
                    ['id' => 2, 'name' => 'Dismissed', 'first_air_date' => '2015-02-08', 'poster_path' => null, 'overview' => 'o', 'vote_average' => 8.5],
                ],
            ]),
        ]);

        $this->assertSame([], $this->service->forUser($user));
    }

    public function test_returns_empty_when_nothing_is_tracked(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $this->assertSame([], $this->service->forUser($user));
        Http::assertNothingSent();
    }
}
