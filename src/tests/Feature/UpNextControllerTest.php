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
use Tests\TestCase;

class UpNextControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_shows_the_earliest_unwatched_aired_episode_per_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['title' => 'Breaking Bad']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2, 'air_date' => today()->subDay(), 'title' => 'Second Aired']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subWeek(), 'title' => 'First Aired']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 3, 'air_date' => today()->addWeek(), 'title' => 'Not Aired Yet']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $upNext = $response->viewData('upNext');
        $this->assertCount(1, $upNext);
        $this->assertSame('First Aired', $upNext[0]['episode']->title);
    }

    public function test_omits_shows_with_nothing_unwatched(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertCount(0, $response->viewData('upNext'));
    }

    public function test_ignores_shows_that_are_not_actively_watching(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'paused']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertCount(0, $response->viewData('upNext'));
    }

    public function test_shows_the_full_movie_watchlist(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['title' => 'A Movie']);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('A Movie');
    }
}
