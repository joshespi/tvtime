<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Models\WatchStatus;
use App\Support\TrackingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The small usability behaviours: idempotent watch marks, undo, redirect
 * anchors, season progress, dashboard backlog counts and the error pages.
 */
class PolishTest extends TestCase
{
    use RefreshDatabase;

    private function trackedEpisode(User $user, array $episode = []): Episode
    {
        $episode = Episode::factory()->create($episode + ['air_date' => today()->subWeek()]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $episode->show_id, 'status' => 'watching']);

        return $episode;
    }

    public function test_explicit_watched_flag_is_idempotent_for_episodes(): void
    {
        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1]);
        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1]);

        $this->assertNotNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 0]);
        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 0]);

        $this->assertNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);
    }

    public function test_toggle_flashes_a_status_and_an_undo_for_the_opposite_state(): void
    {
        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1]);

        $response->assertSessionHas('status');
        $response->assertSessionHas('undo.watched', false);
        $response->assertSessionHas('undo.url', route('episodes.toggle-watched', $episode));
    }

    public function test_undo_banner_renders_on_the_next_page(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1]);

        $this->actingAs($user)->get('/dashboard')->assertSee('Undo');
    }

    public function test_toggle_from_the_show_page_redirects_to_the_episode_row(): void
    {
        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);
        $showUrl = route('shows.show', $episode->show_id);

        $this->actingAs($user)
            ->from($showUrl)
            ->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1])
            ->assertRedirect("{$showUrl}#episode-{$episode->id}");
    }

    public function test_mark_season_watches_only_aired_episodes_of_that_season(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        $aired = Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 1, 'air_date' => today()->subWeek()]);
        $future = Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 2, 'air_date' => today()->addWeek()]);
        $otherSeason = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subYear()]);

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/2/mark-watched")->assertRedirect();

        $this->assertNotNull(WatchStatus::query()->where('episode_id', $aired->id)->first()?->watched_at);
        $this->assertDatabaseMissing('watch_status', ['episode_id' => $future->id]);
        $this->assertDatabaseMissing('watch_status', ['episode_id' => $otherSeason->id]);
    }

    public function test_mark_season_finishes_a_show_that_is_now_caught_up(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'air_date' => today()->subWeek()]);

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched");

        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
    }

    public function test_mark_season_requires_tracking_the_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched")->assertNotFound();
    }

    public function test_explicit_watched_flag_is_idempotent_for_movies(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => Movie::factory()]);

        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched", ['watched' => 1]);
        $this->actingAs($user)->post("/movies/{$userMovie->id}/toggle-watched", ['watched' => 1]);

        $this->assertSame(1, $userMovie->watches()->count());
        $this->assertTrue($userMovie->fresh()->isFinished());
    }

    public function test_show_page_marks_upcoming_episodes_and_opens_the_current_season(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        $done = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subYear()]);
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $done->id]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 1, 'air_date' => today()->subWeek()]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 2, 'air_date' => today()->addDays(3)]);

        $response = $this->actingAs($user)->get(route('shows.show', $show));

        $response->assertOk()
            ->assertSee('1/1')
            ->assertSee('0/2')
            ->assertSee('in 3 days');

        $this->assertMatchesRegularExpression('/id="season-2"\s+open/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/id="season-1"\s+open/', $response->getContent());
    }

    public function test_dashboard_shows_how_many_more_episodes_are_waiting(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);

        foreach ([1, 2, 3, 4] as $number) {
            Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => $number, 'air_date' => today()->subDays(10 - $number)]);
        }

        $this->actingAs($user)->get('/dashboard')->assertSee('+3 more');
    }

    public function test_dashboard_welcomes_a_user_who_tracks_nothing(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('Nothing is being tracked yet');
    }

    public function test_pages_carry_their_own_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/calendar')->assertSee('<title>Calendar · ', false);
    }

    public function test_history_says_when_it_is_capped(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $episodes = Episode::factory()->count(201)->create(['show_id' => $show->id]);
        WatchStatus::insert($episodes->map(fn ($episode) => [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'watched_at' => now(),
        ])->all());

        $this->actingAs($user)->get('/history')->assertSee('Showing your latest 200 entries');
    }

    public function test_history_has_no_cap_note_below_the_limit(): void
    {
        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $this->actingAs($user)->get('/history')->assertDontSee('Showing your latest');
    }

    public function test_calendar_links_movie_releases(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['release_date' => today()]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $this->actingAs($user)->get('/calendar')
            ->assertSee(route('catalog.show', ['type' => 'movie', 'tmdbId' => $movie->tmdb_id]), false);
    }

    public function test_unknown_routes_render_the_branded_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
    }

    public function test_anchor_survives_a_query_string_on_the_show_page(): void
    {
        $user = User::factory()->create();
        $episode = $this->trackedEpisode($user);
        $showUrl = route('shows.show', $episode->show_id).'?ref=1';

        $this->actingAs($user)
            ->from($showUrl)
            ->post("/episodes/{$episode->id}/toggle-watched", ['watched' => 1])
            ->assertRedirect("{$showUrl}#episode-{$episode->id}");
    }

    public function test_mark_season_with_nothing_to_mark_says_so(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'air_date' => null]);

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched")
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'nothing left to mark'));
    }

    public function test_mark_season_button_is_hidden_when_only_undated_episodes_remain(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'air_date' => null]);

        $this->actingAs($user)->get(route('shows.show', $show))->assertDontSee('Mark season watched');
    }

    public function test_the_session_expired_page_renders(): void
    {
        // CSRF verification is skipped under test, so a real 419 can't be
        // provoked — render the view the framework would hand it.
        $this->view('errors.419')->assertSee('Session expired')->assertSee('Sign in');
    }
}
