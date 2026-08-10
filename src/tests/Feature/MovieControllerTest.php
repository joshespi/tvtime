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

    public function test_index_excludes_watched_movies(): void
    {
        $user = User::factory()->create();
        $watched = Movie::factory()->create(['title' => 'Already Watched']);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $watched->id]);

        $response = $this->actingAs($user)->get('/movies');

        $response->assertOk();
        $response->assertDontSee('Already Watched');
    }

    public function test_toggle_watched_marks_an_unwatched_movie_watched(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'watched_at' => null]);

        $response = $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");

        $response->assertRedirect();
        $this->assertNotNull($userMovie->fresh()->watched_at);
    }

    public function test_toggle_watched_marks_a_watched_movie_unwatched(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->watched()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched");

        $this->assertNull($userMovie->fresh()->watched_at);
    }

    public function test_toggle_watched_forbids_another_users_movie(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create();

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched")->assertForbidden();
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
