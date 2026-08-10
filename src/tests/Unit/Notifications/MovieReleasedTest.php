<?php

namespace Tests\Unit\Notifications;

use App\Models\Movie;
use App\Models\User;
use App\Notifications\MovieReleased;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class MovieReleasedTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_uses_mail_and_web_push(): void
    {
        $movie = Movie::factory()->create();

        $this->assertSame(['mail', WebPushChannel::class], (new MovieReleased($movie))->via(User::factory()->make()));
    }

    public function test_to_mail_includes_the_movie_title(): void
    {
        $movie = Movie::factory()->create(['title' => 'The Matrix']);

        $mail = (new MovieReleased($movie))->toMail(User::factory()->make());

        $this->assertSame('Released today: The Matrix', $mail->subject);
        $this->assertStringContainsString('"The Matrix" is out today.', implode(' ', $mail->introLines));
        $this->assertSame(route('movies.index'), $mail->actionUrl);
    }

    public function test_to_web_push_includes_the_movie_title(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $movie = Movie::factory()->create(['title' => 'The Matrix', 'poster_path' => '/poster.jpg']);

        $notification = new MovieReleased($movie);
        $push = $notification->toWebPush(User::factory()->make(), $notification)->toArray();

        $this->assertSame('Released today: The Matrix', $push['title']);
        $this->assertSame('"The Matrix" is out today.', $push['body']);
        $this->assertSame('https://image.tmdb.org/t/p/w92/poster.jpg', $push['icon']);
        $this->assertSame(route('movies.index'), $push['data']['url']);
        $this->assertSame("movie-{$movie->id}", $push['tag']);
    }

    public function test_to_web_push_falls_back_to_the_default_icon_without_a_poster(): void
    {
        $movie = Movie::factory()->create(['poster_path' => null]);

        $notification = new MovieReleased($movie);
        $push = $notification->toWebPush(User::factory()->make(), $notification)->toArray();

        $this->assertSame('/icon-192.png', $push['icon']);
    }
}
