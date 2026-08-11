<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/profile/stats')->assertRedirect('/login');
    }

    public function test_counts_watched_episodes_and_movies(): void
    {
        $user = User::factory()->create();

        $episode = Episode::factory()->create();
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $movie = UserMovie::factory()->watched()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/profile/stats');

        $response->assertOk();
        $this->assertSame(1, $response->viewData('episodesWatchedTotal'));
        $this->assertSame(1, $response->viewData('moviesWatchedTotal'));
    }

    public function test_only_counts_watched_items_this_year_for_the_this_year_figures(): void
    {
        $user = User::factory()->create();

        $episode = Episode::factory()->create();
        WatchStatus::factory()->create(['user_id' => $user->id, 'episode_id' => $episode->id, 'watched_at' => Carbon::now()->subYears(2)]);

        $response = $this->actingAs($user)->get('/profile/stats');

        $this->assertSame(1, $response->viewData('episodesWatchedTotal'));
        $this->assertSame(0, $response->viewData('episodesWatchedThisYear'));
    }

    public function test_breaks_down_shows_by_status(): void
    {
        $user = User::factory()->create();

        UserShow::factory()->create(['user_id' => $user->id, 'status' => 'watching']);
        UserShow::factory()->create(['user_id' => $user->id, 'status' => 'watching']);
        UserShow::factory()->create(['user_id' => $user->id, 'status' => 'dropped']);

        $response = $this->actingAs($user)->get('/profile/stats');

        $counts = $response->viewData('showStatusCounts');
        $this->assertEquals(2, $counts['watching']);
        $this->assertEquals(1, $counts['dropped']);
    }

    public function test_tallies_top_genres_from_tracked_shows_and_watched_movies(): void
    {
        $user = User::factory()->create();

        $show = Show::factory()->create(['genres' => ['Drama', 'Crime']]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $movie = Movie::factory()->create(['genres' => ['Drama']]);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $response = $this->actingAs($user)->get('/profile/stats');

        $topGenres = $response->viewData('topGenres');
        $this->assertSame(2, $topGenres['Drama']);
        $this->assertSame(1, $topGenres['Crime']);
    }

    public function test_computes_the_longest_consecutive_day_streak(): void
    {
        $user = User::factory()->create();

        $episodes = Episode::factory()->count(3)->create();
        WatchStatus::factory()->create(['user_id' => $user->id, 'episode_id' => $episodes[0]->id, 'watched_at' => Carbon::now()->subDays(2)]);
        WatchStatus::factory()->create(['user_id' => $user->id, 'episode_id' => $episodes[1]->id, 'watched_at' => Carbon::now()->subDays(1)]);
        WatchStatus::factory()->create(['user_id' => $user->id, 'episode_id' => $episodes[2]->id, 'watched_at' => Carbon::now()]);

        $response = $this->actingAs($user)->get('/profile/stats');

        $this->assertSame(3, $response->viewData('longestStreak'));
    }

    public function test_only_counts_this_users_activity(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $episode = Episode::factory()->create();
        WatchStatus::factory()->watched()->create(['user_id' => $other->id, 'episode_id' => $episode->id]);

        $response = $this->actingAs($user)->get('/profile/stats');

        $this->assertSame(0, $response->viewData('episodesWatchedTotal'));
    }
}
