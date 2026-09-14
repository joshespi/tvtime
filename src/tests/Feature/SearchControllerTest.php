<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
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

        $this->actingAs($user)->post('/search', ['type' => 'show', 'external_id' => '1396']);
        $this->actingAs($user)->post('/search', ['type' => 'show', 'external_id' => '1396']);

        $this->assertDatabaseCount('user_shows', 1);
    }

    public function test_store_show_reports_a_friendly_error_when_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'show',
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
            'external_id' => '603',
        ]);

        $response->assertRedirect(route('movies.index'));
        $movie = Movie::query()->where('tmdb_id', '603')->firstOrFail();
        $this->assertDatabaseHas('user_movies', ['user_id' => $user->id, 'movie_id' => $movie->id]);
    }

    public function test_store_movie_reports_a_friendly_error_when_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $response = $this->actingAs($user)->post('/search', [
            'type' => 'movie',
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
            'external_id' => '1',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_store_returns_json_and_does_not_redirect_when_the_request_wants_json(): void
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

        $response = $this->actingAs($user)->postJson('/search', [
            'type' => 'show',
            'external_id' => '1396',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Now tracking "Breaking Bad".']);
        $this->assertDatabaseCount('user_shows', 1);
    }

    public function test_store_returns_a_json_validation_error_when_the_request_wants_json(): void
    {
        // Regression: shouldRenderJsonWhen() in bootstrap/app.php used to
        // replace Laravel's default expectsJson() check instead of adding to
        // it, so this — an automatic ValidationException, not the controller's
        // own respondError() branch — used to redirect even on an XHR request,
        // and trackButton()'s `await response.json()` would throw on the
        // followed redirect's HTML body.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/search', [
            'type' => 'bogus',
            'external_id' => '1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('type');
    }

    public function test_store_returns_a_json_error_when_the_show_is_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $response = $this->actingAs($user)->postJson('/search', [
            'type' => 'show',
            'external_id' => 'missing',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Could not find that show on TMDB.']);
    }

    public function test_store_returns_json_for_a_movie_when_the_request_wants_json(): void
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

        $response = $this->actingAs($user)->postJson('/search', [
            'type' => 'movie',
            'external_id' => '603',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Added "The Matrix" to your watchlist.']);
        $this->assertDatabaseCount('user_movies', 1);
    }

    public function test_store_requeues_a_movie_that_was_already_watched(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'title' => 'The Matrix']);
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'release_date' => '1999-03-31',
            ]),
        ]);

        $response = $this->actingAs($user)->post('/search', ['type' => 'movie', 'external_id' => '603']);

        $response->assertRedirect(route('movies.index'));
        $response->assertSessionHas('status', '"The Matrix" is back on your watchlist — you\'ve watched it once before.');
        $this->assertNull($userMovie->fresh()->watched_at);
        $this->assertDatabaseCount('movie_watches', 1);
    }

    public function test_search_results_say_what_the_user_already_tracks(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603']);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'movie', 'id' => 603, 'title' => 'The Matrix', 'release_date' => '1999-03-31', 'poster_path' => '/m.jpg', 'overview' => 'o', 'vote_average' => 8.0],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get('/movies?q=matrix');

        $response->assertOk();
        $tracked = $response->viewData('results')[0]['tracked'];
        $this->assertStringContainsString('Watched once', $tracked['label']);
        $this->assertSame(route('movies.watch-again', $user->movies()->first()), $tracked['action']['url']);
        $this->assertTrue($tracked['action']['post']);
    }

    public function test_search_results_for_untracked_titles_are_not_annotated(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'movie', 'id' => 603, 'title' => 'The Matrix', 'release_date' => '1999-03-31', 'poster_path' => '/m.jpg', 'overview' => 'o', 'vote_average' => 8.0],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get('/movies?q=matrix');

        $response->assertOk();
        $this->assertNull($response->viewData('results')[0]['tracked']);
        $response->assertSee('Add to watchlist');
    }
}
