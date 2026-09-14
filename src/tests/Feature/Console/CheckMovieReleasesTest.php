<?php

namespace Tests\Feature\Console;

use App\Models\Movie;
use App\Models\NotificationSent;
use App\Models\User;
use App\Models\UserMovie;
use App\Notifications\MovieReleased;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckMovieReleasesTest extends TestCase
{
    use RefreshDatabase;

    private function fakeTmdbRefresh(string $tmdbId): void
    {
        Http::fake([
            "api.themoviedb.org/3/movie/{$tmdbId}*" => Http::response([
                'id' => (int) $tmdbId,
                'title' => 'A Movie',
                'overview' => 'o',
                'release_date' => today()->toDateString(),
            ]),
        ]);
    }

    public function test_notifies_watchlisted_users_about_movies_releasing_today(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id, 'watched_at' => null]);

        $this->fakeTmdbRefresh('603');

        $this->artisan('app:check-movie-releases')->assertSuccessful();

        // notifications_sent is no longer written at queue-time — it's written
        // by App\Listeners\RecordNotificationSent once a channel actually
        // delivers, which Notification::fake() intentionally bypasses (see that
        // listener's own test). This asserts the command queued the send.
        Notification::assertSentTo($user, MovieReleased::class);
    }

    public function test_does_not_notify_for_movies_not_releasing_today(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()->addWeek()]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id, 'watched_at' => null]);

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'A Movie',
                'overview' => 'o',
                'release_date' => today()->addWeek()->toDateString(),
            ]),
        ]);

        $this->artisan('app:check-movie-releases')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_for_already_watched_movies(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()]);
        UserMovie::factory()->watched()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $this->fakeTmdbRefresh('603');

        $this->artisan('app:check-movie-releases')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_the_same_user_twice_for_the_same_movie(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id, 'watched_at' => null]);

        NotificationSent::factory()->create([
            'user_id' => $user->id,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'channel' => 'mail',
        ]);

        $this->fakeTmdbRefresh('603');

        $this->artisan('app:check-movie-releases')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_ignores_movies_nobody_watchlisted(): void
    {
        Notification::fake();

        Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()]);

        Http::fake();

        $this->artisan('app:check-movie-releases')->assertSuccessful();

        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_continues_when_a_movies_refresh_fails(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $movie = Movie::factory()->create(['tmdb_id' => '603', 'release_date' => today()]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id, 'watched_at' => null]);

        Http::fake(['api.themoviedb.org/3/movie/*' => Http::response(null, 500)]);

        $this->artisan('app:check-movie-releases')->assertSuccessful();
    }
}
