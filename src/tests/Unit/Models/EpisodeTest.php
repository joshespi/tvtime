<?php

namespace Tests\Unit\Models;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_formats_season_and_episode_number(): void
    {
        $episode = Episode::factory()->make(['season' => 2, 'episode_number' => 7]);

        $this->assertSame('S2E7', $episode->code());
    }

    public function test_label_includes_the_title_when_present(): void
    {
        $episode = Episode::factory()->make(['season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);

        $this->assertSame('S1E1 — Pilot', $episode->label());
    }

    public function test_label_omits_the_dash_when_title_is_missing(): void
    {
        $episode = Episode::factory()->make(['season' => 1, 'episode_number' => 1, 'title' => null]);

        $this->assertSame('S1E1', $episode->label());
    }

    public function test_scope_unwatched_aired_for_excludes_unaired_episodes(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $future = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->addWeek()]);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        $results = Episode::query()->unwatchedAiredFor($user->id)->get();

        $this->assertFalse($results->contains('id', $future->id));
    }

    public function test_scope_unwatched_aired_for_excludes_episodes_marked_watched(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $watched = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);
        $unwatched = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);

        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $watched->id]);

        $results = Episode::query()->unwatchedAiredFor($user->id)->get();

        $this->assertFalse($results->contains('id', $watched->id));
        $this->assertTrue($results->contains('id', $unwatched->id));
    }

    public function test_scope_unwatched_aired_for_is_scoped_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $show = Show::factory()->create();

        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subWeek()]);
        WatchStatus::factory()->watched()->create(['user_id' => $userA->id, 'episode_id' => $episode->id]);

        $results = Episode::query()->unwatchedAiredFor($userB->id)->get();

        $this->assertTrue($results->contains('id', $episode->id));
    }

    public function test_mark_watched_through_marks_earlier_episodes_in_the_same_season(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $e1 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $e2 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);
        $e3 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 3]);

        $e2->markWatchedThrough($user->id);

        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e1->id]);
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e2->id]);
        $this->assertDatabaseMissing('watch_status', ['user_id' => $user->id, 'episode_id' => $e3->id]);
    }

    public function test_mark_watched_through_spans_prior_seasons(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $s1e5 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 5]);
        $s2e1 = Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 1]);
        $s2e2 = Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 2]);
        $s2e3 = Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 3]);

        $s2e2->markWatchedThrough($user->id);

        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $s1e5->id]);
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $s2e1->id]);
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $s2e2->id]);
        $this->assertDatabaseMissing('watch_status', ['user_id' => $user->id, 'episode_id' => $s2e3->id]);
    }

    public function test_mark_watched_through_does_not_touch_other_shows(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $otherShow = Show::factory()->create();

        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $otherEpisode = Episode::factory()->create(['show_id' => $otherShow->id, 'season' => 1, 'episode_number' => 1]);

        $episode->markWatchedThrough($user->id);

        $this->assertDatabaseMissing('watch_status', ['user_id' => $user->id, 'episode_id' => $otherEpisode->id]);
    }

    public function test_mark_watched_through_is_idempotent(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);

        $episode->markWatchedThrough($user->id);
        $episode->markWatchedThrough($user->id);

        $this->assertDatabaseCount('watch_status', 1);
    }
}
