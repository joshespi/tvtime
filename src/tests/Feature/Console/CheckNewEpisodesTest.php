<?php

namespace Tests\Feature\Console;

use App\Models\Episode;
use App\Models\NotificationSent;
use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use App\Notifications\NewEpisodeAired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckNewEpisodesTest extends TestCase
{
    use RefreshDatabase;

    private function fakeTmdbRefresh(string $tmdbId): void
    {
        Http::fake([
            "api.themoviedb.org/3/tv/{$tmdbId}/season/*" => Http::response(['episodes' => []]),
            "api.themoviedb.org/3/tv/{$tmdbId}*" => Http::response([
                'id' => (int) $tmdbId,
                'name' => 'A Show',
                'overview' => 'o',
                'number_of_seasons' => 0,
                'first_air_date' => '2020-01-01',
            ]),
        ]);
    }

    public function test_notifies_watching_users_about_episodes_airing_today(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()]);

        $this->fakeTmdbRefresh('1396');

        $this->artisan('app:check-new-episodes')->assertSuccessful();

        // notifications_sent is no longer written at queue-time — it's written
        // by App\Listeners\RecordNotificationSent once a channel actually
        // delivers, which Notification::fake() intentionally bypasses (see that
        // listener's own test). This asserts the command queued the send.
        Notification::assertSentTo($user, NewEpisodeAired::class);
    }

    public function test_does_not_notify_for_episodes_not_airing_today(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()->subDay()]);

        $this->fakeTmdbRefresh('1396');

        $this->artisan('app:check-new-episodes')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_users_who_are_not_actively_watching(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'paused']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()]);

        $this->fakeTmdbRefresh('1396');

        $this->artisan('app:check-new-episodes')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_the_same_user_twice_for_the_same_episode(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()]);

        NotificationSent::factory()->create([
            'user_id' => $user->id,
            'subject_type' => Episode::class,
            'subject_id' => $episode->id,
            'channel' => 'mail',
        ]);

        $this->fakeTmdbRefresh('1396');

        $this->artisan('app:check-new-episodes')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_ignores_shows_nobody_tracks(): void
    {
        Notification::fake();

        Show::factory()->create(['tmdb_id' => '1396']);

        Http::fake();

        $this->artisan('app:check-new-episodes')->assertSuccessful();

        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_continues_when_a_shows_refresh_fails(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $show = Show::factory()->create(['tmdb_id' => '1396']);
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id, 'status' => 'watching']);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => today()]);

        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 500)]);

        $this->artisan('app:check-new-episodes')->assertSuccessful();
    }
}
