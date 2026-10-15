<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use App\Models\WatchStatus;
use App\Support\TrackingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WatchStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $episode = Episode::factory()->create();

        $this->post("/episodes/{$episode->id}/toggle-watched")->assertRedirect('/login');
    }

    public function test_toggle_marks_an_unwatched_episode_watched(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $episode->show_id]);

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $response->assertRedirect();
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $episode->id]);
        $this->assertNotNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);
    }

    public function test_toggle_marks_a_watched_episode_unwatched(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $episode->show_id]);
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);
    }

    public function test_toggle_is_scoped_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $episode = Episode::factory()->create();
        UserShow::factory()->create(['user_id' => $userA->id, 'show_id' => $episode->show_id]);

        $this->actingAs($userA)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertDatabaseHas('watch_status', ['user_id' => $userA->id, 'episode_id' => $episode->id]);
        $this->assertDatabaseMissing('watch_status', ['user_id' => $userB->id, 'episode_id' => $episode->id]);
    }

    public function test_toggle_forbids_an_episode_of_a_show_the_user_does_not_track(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $response->assertForbidden();
        $this->assertDatabaseMissing('watch_status', ['user_id' => $user->id, 'episode_id' => $episode->id]);
    }

    public function test_mark_through_forbids_an_episode_of_a_show_the_user_does_not_track(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/mark-through");

        $response->assertForbidden();
    }

    public function test_mark_through_marks_earlier_episodes_watched(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $e1 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $e2 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);

        $response = $this->actingAs($user)->post("/episodes/{$e2->id}/mark-through");

        $response->assertRedirect();
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e1->id]);
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e2->id]);
    }

    public function test_watching_the_last_aired_episode_finishes_the_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
    }

    public function test_unwatching_an_episode_puts_a_finished_show_back_to_watching(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->finished()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_mark_through_finishes_a_show_caught_up_in_one_go(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => today()->subMonth()]);
        $last = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2, 'air_date' => today()->subWeek()]);

        $this->actingAs($user)->post("/episodes/{$last->id}/mark-through");

        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
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
        $this->trackShow($user, $show);

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
        $userShow = $this->trackShow($user, $show);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'air_date' => today()->subWeek()]);

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched");

        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
    }

    public function test_mark_season_requires_tracking_the_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched")->assertForbidden();
    }

    public function test_mark_season_with_nothing_to_mark_says_so(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $this->trackShow($user, $show);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'air_date' => null]);

        $this->actingAs($user)->post("/shows/{$show->id}/seasons/1/mark-watched")
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'nothing left to mark'));
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
}
