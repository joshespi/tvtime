<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/history')->assertRedirect('/login');
    }

    public function test_lists_watched_movies_and_episodes_sorted_by_most_recent(): void
    {
        $user = User::factory()->create();

        $movie = Movie::factory()->create(['title' => 'Old Movie']);
        UserMovie::factory()->create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'watched_at' => now()->subDays(2),
        ]);

        $show = Show::factory()->create(['title' => 'Recent Show']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        WatchStatus::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'watched_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $history = $response->viewData('history');
        $this->assertSame(['show', 'movie'], $history->pluck('type')->all());
    }

    public function test_excludes_unwatched_items(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id, 'watched_at' => null]);

        $response = $this->actingAs($user)->get('/history');

        $this->assertCount(0, $response->viewData('history'));
    }

    public function test_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $movie = Movie::factory()->create();
        UserMovie::factory()->watched()->create(['user_id' => $other->id, 'movie_id' => $movie->id]);

        $response = $this->actingAs($user)->get('/history');

        $this->assertCount(0, $response->viewData('history'));
    }
}
