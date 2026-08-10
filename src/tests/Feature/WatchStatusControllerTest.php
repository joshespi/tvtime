<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $response->assertRedirect();
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $episode->id]);
        $this->assertNotNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);
    }

    public function test_toggle_marks_a_watched_episode_unwatched(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        WatchStatus::factory()->watched()->create(['user_id' => $user->id, 'episode_id' => $episode->id]);

        $this->actingAs($user)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertNull(WatchStatus::query()->where('episode_id', $episode->id)->first()->watched_at);
    }

    public function test_toggle_is_scoped_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $episode = Episode::factory()->create();

        $this->actingAs($userA)->post("/episodes/{$episode->id}/toggle-watched");

        $this->assertDatabaseHas('watch_status', ['user_id' => $userA->id, 'episode_id' => $episode->id]);
        $this->assertDatabaseMissing('watch_status', ['user_id' => $userB->id, 'episode_id' => $episode->id]);
    }

    public function test_mark_through_marks_earlier_episodes_watched(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        $e1 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1]);
        $e2 = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 2]);

        $response = $this->actingAs($user)->post("/episodes/{$e2->id}/mark-through");

        $response->assertRedirect();
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e1->id]);
        $this->assertDatabaseHas('watch_status', ['user_id' => $user->id, 'episode_id' => $e2->id]);
    }
}
