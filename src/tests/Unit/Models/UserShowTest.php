<?php

namespace Tests\Unit\Models;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use App\Models\WatchStatus;
use App\Support\TrackingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_excludes_paused_and_dropped(): void
    {
        $user = User::factory()->create();

        UserShow::factory()->create(['user_id' => $user->id]);
        UserShow::factory()->finished()->create(['user_id' => $user->id]);
        UserShow::factory()->paused()->create(['user_id' => $user->id]);
        UserShow::factory()->dropped()->create(['user_id' => $user->id]);

        $statuses = $user->shows()->active()->pluck('status');

        $this->assertEqualsCanonicalizing(
            [TrackingStatus::Watching, TrackingStatus::Finished],
            $statuses->all(),
        );
    }

    public function test_refresh_finished_status_finishes_a_fully_caught_up_show(): void
    {
        [$userShow, $episode] = $this->trackedShowWithAiredEpisode();
        WatchStatus::factory()->watched()->create(['user_id' => $userShow->user_id, 'episode_id' => $episode->id]);

        $userShow->refreshFinishedStatus();

        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
    }

    public function test_refresh_finished_status_leaves_a_show_with_unwatched_episodes_watching(): void
    {
        [$userShow] = $this->trackedShowWithAiredEpisode();

        $userShow->refreshFinishedStatus();

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_refresh_finished_status_unfinishes_a_show_once_a_new_episode_airs(): void
    {
        [$userShow, $episode] = $this->trackedShowWithAiredEpisode();
        $userShow->update(['status' => TrackingStatus::Finished]);
        WatchStatus::factory()->watched()->create(['user_id' => $userShow->user_id, 'episode_id' => $episode->id]);

        Episode::factory()->create([
            'show_id' => $userShow->show_id,
            'season' => 1,
            'episode_number' => 2,
            'air_date' => today()->subDay(),
        ]);

        $userShow->refreshFinishedStatus();

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_refresh_finished_status_does_not_override_paused_or_dropped(): void
    {
        foreach ([TrackingStatus::Paused, TrackingStatus::Dropped] as $status) {
            [$userShow, $episode] = $this->trackedShowWithAiredEpisode();
            $userShow->update(['status' => $status]);
            WatchStatus::factory()->watched()->create(['user_id' => $userShow->user_id, 'episode_id' => $episode->id]);

            $userShow->refreshFinishedStatus();

            $this->assertSame($status, $userShow->fresh()->status);
        }
    }

    public function test_refresh_finished_status_ignores_a_show_whose_episodes_have_not_synced(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $userShow->refreshFinishedStatus();

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_mark_watched_records_the_watch_and_finishes_a_caught_up_show(): void
    {
        [$userShow, $episode] = $this->trackedShowWithAiredEpisode();

        $userShow->markWatched([$episode->id]);

        $this->assertNotNull(WatchStatus::where('user_id', $userShow->user_id)->where('episode_id', $episode->id)->value('watched_at'));
        $this->assertSame(TrackingStatus::Finished, $userShow->fresh()->status);
    }

    public function test_mark_unwatched_clears_the_watch_and_unfinishes_the_show(): void
    {
        [$userShow, $episode] = $this->trackedShowWithAiredEpisode();
        $userShow->markWatched([$episode->id]);

        $userShow->markUnwatched($episode);

        $this->assertNull(WatchStatus::where('user_id', $userShow->user_id)->where('episode_id', $episode->id)->value('watched_at'));
        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    /** @return array{0: UserShow, 1: Episode} */
    private function trackedShowWithAiredEpisode(): array
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $episode = Episode::factory()->create([
            'show_id' => $show->id,
            'season' => 1,
            'episode_number' => 1,
            'air_date' => today()->subWeek(),
        ]);

        return [$userShow, $episode];
    }
}
