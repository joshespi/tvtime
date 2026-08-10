<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/search/suggest?q=matrix')->assertRedirect('/login');
    }

    public function test_suggest_returns_empty_for_a_short_query(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/search/suggest?q=a');

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_suggest_returns_the_top_results(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'movie', 'id' => 1, 'title' => 'The Matrix', 'release_date' => '1999-03-31', 'poster_path' => '/m.jpg', 'overview' => 'o', 'vote_average' => 8.0],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->getJson('/search/suggest?q=matrix');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => 'The Matrix', 'type' => 'movie']);
    }

    public function test_store_adds_a_show_to_the_users_watchlist(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/*' => Http::response(['episodes' => []]),
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'overview' => 'A chemistry teacher...',
                'number_of_seasons' => 0,
                'first_air_date' => '2008-01-20',
            ]),
        ]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'show',
            'source' => 'tmdb',
            'external_id' => '1396',
        ]);

        $response->assertRedirect(route('shows.index'));
        $show = Show::query()->where('tmdb_id', '1396')->firstOrFail();
        $this->assertDatabaseHas('user_shows', ['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
    }

    public function test_store_show_is_idempotent(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'overview' => 'A chemistry teacher...',
                'number_of_seasons' => 0,
                'first_air_date' => '2008-01-20',
            ]),
        ]);

        $this->actingAs($user)->post('/search', ['type' => 'show', 'source' => 'tmdb', 'external_id' => '1396']);
        $this->actingAs($user)->post('/search', ['type' => 'show', 'source' => 'tmdb', 'external_id' => '1396']);

        $this->assertDatabaseCount('user_shows', 1);
    }

    public function test_store_show_reports_a_friendly_error_when_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/tv/*' => Http::response(null, 404),
            'api.trakt.tv/shows/*' => Http::response(null, 404),
        ]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'show',
            'source' => 'tmdb',
            'external_id' => 'missing',
        ]);

        $response->assertSessionHasErrors('external_id');
        $this->assertDatabaseCount('shows', 0);
    }

    public function test_store_adds_a_movie_to_the_users_watchlist(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'release_date' => '1999-03-31',
            ]),
        ]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'movie',
            'source' => 'tmdb',
            'external_id' => '603',
        ]);

        $response->assertRedirect(route('movies.index'));
        $movie = Movie::query()->where('tmdb_id', '603')->firstOrFail();
        $this->assertDatabaseHas('user_movies', ['user_id' => $user->id, 'movie_id' => $movie->id]);
    }

    public function test_store_movie_reports_a_friendly_error_when_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/movie/*' => Http::response(null, 404),
            'api.trakt.tv/movies/*' => Http::response(null, 404),
        ]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'movie',
            'source' => 'tmdb',
            'external_id' => 'missing',
        ]);

        $response->assertSessionHasErrors('external_id');
        $this->assertDatabaseCount('movies', 0);
    }

    public function test_store_validates_the_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'bogus',
            'source' => 'tmdb',
            'external_id' => '1',
        ]);

        $response->assertSessionHasErrors('type');
    }
}
