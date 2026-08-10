<?php

namespace Tests\Unit\Models;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_episodes_with_watch_state_flags_watched_episodes(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $watched = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $unwatched = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);

        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $watched->id]);

        $episodes = $show->episodesWithWatchState($user->id)->keyBy('id');

        $this->assertTrue($episodes[$watched->id]->watched);
        $this->assertFalse($episodes[$unwatched->id]->watched);
    }

    public function test_episodes_with_watch_state_is_scoped_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $show = Show::factory()->create();

        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        WatchStatus::factory()->watched()->create(['user_id' => $userA->id, 'episode_id' => $episode->id]);

        $episodes = $show->episodesWithWatchState($userB->id)->keyBy('id');

        $this->assertFalse($episodes[$episode->id]->watched);
    }

    public function test_has_unwatched_before_flags_episodes_after_a_gap(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $e1 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $e2 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);
        $e3 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 3]);

        // e1 left unwatched, e2 watched: e3 should see an earlier gap.
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $e2->id]);

        $episodes = $show->episodesWithWatchState($user->id)->keyBy('id');

        $this->assertFalse($episodes[$e1->id]->hasUnwatchedBefore);
        $this->assertTrue($episodes[$e2->id]->hasUnwatchedBefore);
        $this->assertTrue($episodes[$e3->id]->hasUnwatchedBefore);
    }

    public function test_episodes_with_watch_state_orders_by_season_then_episode_number(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        Episode::factory()->create(['show_id' => $show->id, 'season' => 2, 'episode_number' => 1]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);

        $episodes = $show->episodesWithWatchState($user->id);

        $this->assertSame(
            [[1, 1], [1, 2], [2, 1]],
            $episodes->map(fn (Episode $episode) => [$episode->season, $episode->episode_number])->all()
        );
    }
}
