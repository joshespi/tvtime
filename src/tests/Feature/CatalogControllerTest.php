<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesTmdb;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use FakesTmdb;
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/catalog/show/1396')->assertRedirect('/login');
    }

    public function test_shows_a_preview_for_an_untracked_show(): void
    {
        $user = User::factory()->create();

        $this->fakeTmdbShow();

        $response = $this->actingAs($user)->get('/catalog/show/1396');

        $response->assertOk();
        $response->assertSee('Breaking Bad');
        $response->assertSee('A chemistry teacher...');
        $response->assertSee('Track');
        $response->assertSee('https://www.imdb.com/title/tt0903747/', false);
    }

    public function test_redirects_to_the_tracked_show_page_when_the_user_already_tracks_it(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $response = $this->actingAs($user)->get('/catalog/show/1396');

        $response->assertRedirect(route('shows.show', $show->id));
        Http::assertNothingSent();
    }

    public function test_404s_when_tmdb_has_no_such_show(): void
    {
        $user = User::factory()->create();

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 404)]);

        $this->actingAs($user)->get('/catalog/show/missing')->assertNotFound();
    }

    public function test_shows_a_preview_for_an_untracked_movie(): void
    {
        $user = User::factory()->create();

        $this->fakeTmdbMovie();

        $response = $this->actingAs($user)->get('/catalog/movie/603');

        $response->assertOk();
        $response->assertSee('The Matrix');
        $response->assertSee('Add to watchlist');
    }

    public function test_shows_watchlist_state_for_a_tracked_movie_using_local_metadata(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'title' => 'The Matrix', 'overview' => 'A hacker...']);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        // Blanket fake with no stubs: a tracked movie's metadata comes from the
        // already-synced local row, not a second TMDB fetch (otherwise this page
        // and /movies could drift into showing two different overviews for the
        // same title) — any request landing here would fail loudly.
        Http::fake();

        $response = $this->actingAs($user)->get('/catalog/movie/603');

        $response->assertOk();
        $response->assertSee('The Matrix');
        $response->assertSee('A hacker...');
        $response->assertSee('On your watchlist');
        Http::assertNothingSent();
    }

    public function test_shows_watched_state_and_watch_again_for_a_finished_movie(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'title' => 'The Matrix']);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        Http::fake();

        $response = $this->actingAs($user)->get('/catalog/movie/603');

        $response->assertOk();
        $response->assertSee('Watched once');
        $response->assertSee('Watch again');
        Http::assertNothingSent();
    }

    public function test_404s_when_tmdb_has_no_such_movie(): void
    {
        $user = User::factory()->create();

        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 404)]);

        $this->actingAs($user)->get('/catalog/movie/missing')->assertNotFound();
    }

    public function test_catalog_route_rejects_a_non_numeric_tmdb_id_without_calling_tmdb(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/catalog/movie/abc')->assertNotFound();

        Http::assertNothingSent();
    }
}
