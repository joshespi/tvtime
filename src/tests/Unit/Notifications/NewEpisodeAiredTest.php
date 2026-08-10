<?php

namespace Tests\Unit\Notifications;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use App\Notifications\NewEpisodeAired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class NewEpisodeAiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_uses_mail_and_web_push(): void
    {
        $episode = Episode::factory()->create();

        $this->assertSame(['mail', WebPushChannel::class], (new NewEpisodeAired($episode))->via(User::factory()->make()));
    }

    public function test_to_mail_includes_the_show_and_episode(): void
    {
        $show = Show::factory()->create(['title' => 'Breaking Bad']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);

        $mail = (new NewEpisodeAired($episode))->toMail(User::factory()->make());

        $this->assertSame('New episode: Breaking Bad', $mail->subject);
        $this->assertStringContainsString('S1E1 — Pilot', implode(' ', $mail->introLines));
        $this->assertStringContainsString('Breaking Bad', implode(' ', $mail->introLines));
        $this->assertSame(route('dashboard'), $mail->actionUrl);
    }

    public function test_to_web_push_includes_the_show_and_episode(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $show = Show::factory()->create(['title' => 'Breaking Bad', 'poster_path' => '/poster.jpg']);
        $episode = Episode::factory()->create(['show_id' => $show->id, 'season' => 1, 'episode_number' => 1, 'title' => 'Pilot']);

        $notification = new NewEpisodeAired($episode);
        $push = $notification->toWebPush(User::factory()->make(), $notification)->toArray();

        $this->assertSame('New episode: Breaking Bad', $push['title']);
        $this->assertSame('S1E1 — Pilot just aired.', $push['body']);
        $this->assertSame('https://image.tmdb.org/t/p/w92/poster.jpg', $push['icon']);
        $this->assertSame(route('dashboard'), $push['data']['url']);
        $this->assertSame("episode-{$episode->id}", $push['tag']);
    }

    public function test_to_web_push_falls_back_to_the_default_icon_without_a_poster(): void
    {
        $show = Show::factory()->create(['poster_path' => null]);
        $episode = Episode::factory()->create(['show_id' => $show->id]);

        $notification = new NewEpisodeAired($episode);
        $push = $notification->toWebPush(User::factory()->make(), $notification)->toArray();

        $this->assertSame('/icon-192.png', $push['icon']);
    }
}
