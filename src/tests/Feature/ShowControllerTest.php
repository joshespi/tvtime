<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_show_page_lists_secondary_source_results_for_a_link_query(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['resolved_source' => 'tmdb']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        Http::fake([
            'api.trakt.tv/search/movie,show*' => Http::response([
                ['type' => 'show', 'show' => ['ids' => ['slug' => 'match'], 'title' => 'A Match', 'year' => 2020]],
            ]),
        ]);

        $response = $this->actingAs($user)->get("/shows/{$show->id}?link_query=match");

        $response->assertOk();
        $response->assertSee('A Match');
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

    public function test_link_source_updates_the_secondary_provider_id(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['resolved_source' => 'tmdb', 'trakt_id' => null]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $response = $this->actingAs($user)->post("/shows/{$show->id}/link-source", [
            'source' => 'trakt',
            'external_id' => 'breaking-bad',
        ]);

        $response->assertRedirect(route('shows.show', $show));
        $this->assertSame('breaking-bad', $show->fresh()->trakt_id);
    }

    public function test_link_source_requires_the_user_to_track_the_show(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $this->actingAs($user)->post("/shows/{$show->id}/link-source", [
            'source' => 'trakt',
            'external_id' => 'breaking-bad',
        ])->assertNotFound();
    }

    public function test_link_source_validates_the_source(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $response = $this->actingAs($user)->post("/shows/{$show->id}/link-source", [
            'source' => 'bogus',
            'external_id' => 'x',
        ]);

        $response->assertSessionHasErrors('source');
    }

    public function test_check_conflicts_flashes_a_status_when_there_are_none(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => null, 'trakt_id' => null]);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);

        $response = $this->actingAs($user)->post("/shows/{$show->id}/check-conflicts");

        $response->assertRedirect(route('shows.show', $show));
        $response->assertSessionHas('status', 'No air-date conflicts found.');
    }

    public function test_check_conflicts_flashes_conflicts_when_found(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396', 'trakt_id' => 'breaking-bad', 'resolved_source' => 'tmdb']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'air_date' => '2008-01-20']);

        Http::fake([
            'api.trakt.tv/shows/breaking-bad/seasons*' => Http::response([
                ['number' => 1, 'episodes' => [
                    ['number' => 1, 'title' => 'Pilot', 'first_aired' => '2008-01-21T00:00:00.000Z', 'ids' => ['trakt' => 111]],
                ]],
            ]),
            'api.trakt.tv/shows/breaking-bad*' => Http::response([
                'ids' => ['slug' => 'breaking-bad'],
                'title' => 'Breaking Bad',
                'number_of_seasons' => 1,
            ]),
        ]);

        $response = $this->actingAs($user)->post("/shows/{$show->id}/check-conflicts");

        $response->assertRedirect(route('shows.show', $show));
        $response->assertSessionHas('conflicts');
        $this->assertSame($episode->id, session('conflicts')[0]['episode_id']);
    }

    public function test_resolve_conflict_updates_the_episode_air_date(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => '2008-01-20']);

        $response = $this->actingAs($user)->post("/episodes/{$episode->id}/resolve-conflict", [
            'air_date' => '2008-01-21',
        ]);

        $response->assertRedirect(route('shows.show', $episode->show_id));
        $this->assertSame('2008-01-21', $episode->fresh()->air_date->toDateString());
    }

    public function test_resolve_conflict_requires_the_user_to_track_the_show(): void
    {
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $this->actingAs($user)->post("/episodes/{$episode->id}/resolve-conflict", [
            'air_date' => '2008-01-21',
        ])->assertNotFound();
    }
}
