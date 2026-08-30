<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use App\Models\UserMovie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/movies')->assertRedirect('/login');
    }

    public function test_index_lists_only_the_authenticated_users_unwatched_movies(): void
    {
        $user = User::factory()->create();
        $mine = Movie::factory()->create(['title' => 'Mine']);
        $theirs = Movie::factory()->create(['title' => 'Theirs']);

        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $mine->id]);
        UserMovie::factory()->create(['movie_id' => $theirs->id]);

        $response = $this->actingAs($user)->get('/movies');

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }

    public function test_index_lists_watched_movies_under_finished_rather_than_on_the_watchlist(): void
    {
        $user = User::factory()->create();
        $watched = Movie::factory()->create(['title' => 'Already Watched']);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $watched->id]);

        $response = $this->actingAs($user)->get('/movies');

        $response->assertOk();
        $response->assertSee('Already Watched');
        $response->assertSee('Finished');
        $this->assertTrue($response->viewData('watchlist')->isEmpty());
        $this->assertCount(1, $response->viewData('finished'));
    }

    public function test_toggle_watched_marks_an_unwatched_movie_watched(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'watched_at' => null]);

        $response = $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");

        $response->assertRedirect();
        $this->assertNotNull($userMovie->fresh()->watched_at);
        $this->assertDatabaseCount('movie_watches', 1);
    }

    public function test_toggle_watched_marks_a_watched_movie_unwatched(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");

        $this->assertNull($userMovie->fresh()->watched_at);
        // Un-marking is an undo, so the viewing it undid goes with it.
        $this->assertDatabaseCount('movie_watches', 0);
    }

    public function test_toggle_watched_on_a_rewatch_keeps_the_earlier_viewing(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'watched_at' => null]);
        $first = $userMovie->watches()->create(['watched_at' => now()->subYear()]);

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");
        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");

        $this->assertDatabaseHas('movie_watches', ['id' => $first->id]);
        $this->assertEquals($first->watched_at, $userMovie->fresh()->watched_at);
    }

    public function test_watch_again_returns_a_finished_movie_to_the_watchlist(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/movies/{$userMovie->id}/watch-again");

        $response->assertRedirect();
        $this->assertNull($userMovie->fresh()->watched_at);
        // The point of "watch again": it's queued up, but still remembered.
        $this->assertDatabaseCount('movie_watches', 1);
    }

    public function test_watch_again_forbids_another_users_movie(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->watched()->create();

        $this->actingAs($user)->post("/movies/{$userMovie->id}/watch-again")->assertForbidden();
        $this->assertNotNull($userMovie->fresh()->watched_at);
    }

    public function test_a_requeued_movie_still_shows_it_was_watched_before(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['title' => 'Seen It']);
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);
        $userMovie->requeue();

        $response = $this->actingAs($user)->get('/movies');

        $response->assertOk();
        $response->assertSee('Watched once');
        $this->assertCount(1, $response->viewData('watchlist'));
    }

    public function test_destroy_watch_removes_a_single_viewing(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id]);
        $watch = $userMovie->watches()->first();

        $response = $this->actingAs($user)->delete("/movie-watches/{$watch->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('movie_watches', ['id' => $watch->id]);
        $this->assertNull($userMovie->fresh()->watched_at);
    }

    public function test_destroy_watch_forbids_another_users_viewing(): void
    {
        $user = User::factory()->create();
        $watch = UserMovie::factory()->watched()->create()->watches()->first();

        $this->actingAs($user)->delete("/movie-watches/{$watch->id}")->assertForbidden();
        $this->assertDatabaseHas('movie_watches', ['id' => $watch->id]);
    }

    public function test_toggle_watched_forbids_another_users_movie(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create();

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched")->assertForbidden();
    }

    public function test_index_filters_by_genre(): void
    {
        $user = User::factory()->create();
        $action = Movie::factory()->create(['title' => 'Action Movie', 'genres' => ['Action']]);
        $comedy = Movie::factory()->create(['title' => 'Comedy Movie', 'genres' => ['Comedy']]);

        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $action->id]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $comedy->id]);

        $response = $this->actingAs($user)->get('/movies?genre=Comedy');

        $response->assertOk();
        $this->assertSame(['Comedy Movie'], $response->viewData('watchlist')->pluck('movie.title')->all());
    }

    public function test_update_rating_sets_a_rating_and_note(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('movies.index'))
            ->patch("/movies/{$userMovie->id}/rating", ['rating' => 7, 'note' => 'Rewatch material.']);

        $response->assertRedirect();
        $this->assertSame(7, $userMovie->fresh()->rating);
        $this->assertSame('Rewatch material.', $userMovie->fresh()->note);
    }

    public function test_update_rating_clears_a_rating(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'rating' => 3, 'note' => 'Skip.']);

        $this->actingAs($user)->patch("/movies/{$userMovie->id}/rating", []);

        $this->assertNull($userMovie->fresh()->rating);
        $this->assertNull($userMovie->fresh()->note);
    }

    public function test_update_rating_rejects_an_out_of_range_rating(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/movies/{$userMovie->id}/rating", ['rating' => 0])
            ->assertSessionHasErrors('rating');

        $this->assertNull($userMovie->fresh()->rating);
    }

    public function test_update_rating_forbids_changing_another_users_movie(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create();

        $this->actingAs($user)
            ->patch("/movies/{$userMovie->id}/rating", ['rating' => 9])
            ->assertForbidden();

        $this->assertNull($userMovie->fresh()->rating);
    }

    public function test_destroy_removes_the_users_watchlist_entry(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/movies/{$userMovie->id}");

        $response->assertRedirect(route('movies.index'));
        $this->assertDatabaseMissing('user_movies', ['id' => $userMovie->id]);
    }

    public function test_destroy_forbids_removing_another_users_movie(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create();

        $this->actingAs($user)->delete("/movies/{$userMovie->id}")->assertForbidden();
        $this->assertDatabaseHas('user_movies', ['id' => $userMovie->id]);
    }
}
