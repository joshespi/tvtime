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
use Illuminate\Support\Facades\Http;
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
        Http::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['title' => 'Breaking Bad']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2, 'air_date' => today()->subDay(), 'title' => 'Second Aired']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subWeek(), 'title' => 'First Aired']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 3, 'air_date' => today()->addWeek(), 'title' => 'Not Aired Yet']);

        // A second watching show, to catch a regression to a single global
        // eager-load limit(1) instead of one per parent — that bug would pass
        // this test with only one show tracked.
        $otherShow = Show::factory()->create(['title' => 'The Wire']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $otherShow->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $otherShow->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subWeek(), 'title' => 'Other Show First Aired']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $upNext = $response->viewData('upNext');
        $this->assertCount(2, $upNext);
        $titles = collect($upNext)->pluck('episode.title');
        $this->assertTrue($titles->contains('First Aired'));
        $this->assertTrue($titles->contains('Other Show First Aired'));
    }

    public function test_omits_shows_with_nothing_unwatched(): void
    {
        Http::fake();

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
        Http::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'paused']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertCount(0, $response->viewData('upNext'));
    }

    public function test_shows_the_full_movie_watchlist(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['title' => 'A Movie']);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('A Movie');
    }

    public function test_lists_the_next_unaired_episode_per_watching_show(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->addWeek()]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2, 'air_date' => today()->addWeeks(2)]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $airingSoon = $response->viewData('airingSoon');
        $this->assertCount(1, $airingSoon);
        $this->assertSame(1, $airingSoon[0]['episode']->episode_number);
    }

    public function test_airing_soon_omits_shows_with_nothing_upcoming(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertCount(0, $response->viewData('airingSoon'));
    }

    public function test_recommendations_are_empty_when_disabled(): void
    {
        $user = User::factory()->create(['recommendations_enabled' => false]);
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        Http::fake();

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertSame([], $response->viewData('recommendations'));
        Http::assertNothingSent();
    }

    public function test_recommendations_are_included_when_enabled(): void
    {
        $user = User::factory()->create(['recommendations_enabled' => true]);
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        Http::fake([
            'api.themoviedb.org/3/tv/1396/recommendations*' => Http::response([
                'results' => [
                    ['id' => 2, 'name' => 'Better Call Saul', 'first_air_date' => '2015-02-08', 'poster_path' => null, 'overview' => 'o', 'vote_average' => 8.5],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertCount(1, $response->viewData('recommendations'));
    }
}
