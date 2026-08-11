<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use App\Support\TrackingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/shows')->assertRedirect('/login');
    }

    public function test_index_lists_only_the_authenticated_users_tracked_shows(): void
    {
        $user = User::factory()->create();
        $mine = Show::factory()->create(['title' => 'Mine']);
        $theirs = Show::factory()->create(['title' => 'Theirs']);

        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $mine->id]);
        UserShow::factory()->create(['show_id' => $theirs->id]);

        $response = $this->actingAs($user)->get('/shows');

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }

    public function test_index_sorts_by_title(): void
    {
        $user = User::factory()->create();
        $zebra = Show::factory()->create(['title' => 'Zebra']);
        $apple = Show::factory()->create(['title' => 'Apple']);

        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $zebra->id]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $apple->id]);

        $response = $this->actingAs($user)->get('/shows?sort=title');

        $response->assertOk();
        $ordered = $response->viewData('tracked')->pluck('show.title')->all();
        $this->assertSame(['Apple', 'Zebra'], $ordered);
    }

    public function test_show_page_renders_episodes_with_watch_state(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);

        $response = $this->actingAs($user)->get("/shows/{$show->id}");

        $response->assertOk();
        $response->assertSee('Pilot');
    }

    public function test_show_page_404s_for_a_show_the_user_does_not_track(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $this->actingAs($user)->get("/shows/{$show->id}")->assertNotFound();
    }

    public function test_index_lists_finished_shows_on_their_own_shelf(): void
    {
        $user = User::factory()->create();
        $caughtUp = Show::factory()->create(['title' => 'Caught Up']);
        $current = Show::factory()->create(['title' => 'Still Going']);

        UserShow::factory()->finished()->create(['user_id' => $user->id, 'show_id' => $caughtUp->id]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $current->id]);

        $response = $this->actingAs($user)->get('/shows');

        $response->assertOk();
        $response->assertSee('Caught Up');
        $this->assertSame(['Still Going'], $response->viewData('tracked')->pluck('show.title')->all());
        $this->assertSame(['Caught Up'], $response->viewData('finished')->pluck('show.title')->all());
    }

    public function test_index_sinks_paused_and_dropped_shows_below_active_ones(): void
    {
        $user = User::factory()->create();
        $apple = Show::factory()->create(['title' => 'Apple']);
        $zebra = Show::factory()->create(['title' => 'Zebra']);

        UserShow::factory()->paused()->create(['user_id' => $user->id, 'show_id' => $apple->id]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $zebra->id]);

        $response = $this->actingAs($user)->get('/shows?sort=title');

        $response->assertOk();
        $this->assertSame(['Zebra', 'Apple'], $response->viewData('tracked')->pluck('show.title')->all());
    }

    public function test_update_status_stops_watching_without_removing_the_show(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('shows.index'))
            ->patch("/shows/{$userShow->id}/status", ['status' => 'dropped']);

        $response->assertRedirect(route('shows.index'));
        $this->assertSame(TrackingStatus::Dropped, $userShow->fresh()->status);
    }

    public function test_update_status_rejects_an_unknown_status(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/shows/{$userShow->id}/status", ['status' => 'abandoned'])
            ->assertSessionHasErrors('status');

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_update_status_forbids_changing_another_users_show(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create();

        $this->actingAs($user)
            ->patch("/shows/{$userShow->id}/status", ['status' => 'dropped'])
            ->assertForbidden();

        $this->assertSame(TrackingStatus::Watching, $userShow->fresh()->status);
    }

    public function test_destroy_removes_the_users_tracked_show(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/shows/{$userShow->id}");

        $response->assertRedirect(route('shows.index'));
        $this->assertDatabaseMissing('user_shows', ['id' => $userShow->id]);
    }

    public function test_destroy_forbids_removing_another_users_show(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create();

        $this->actingAs($user)->delete("/shows/{$userShow->id}")->assertForbidden();
        $this->assertDatabaseHas('user_shows', ['id' => $userShow->id]);
    }
}
