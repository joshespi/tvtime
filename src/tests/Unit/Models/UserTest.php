<?php

namespace Tests\Unit\Models;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Models\WatchStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_relation(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id, 'show_id' => Show::factory()->create()->id]);

        $this->assertTrue($user->shows->contains($userShow));
    }

    public function test_movies_relation(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => Movie::factory()->create()->id]);

        $this->assertTrue($user->movies->contains($userMovie));
    }

    public function test_watch_statuses_relation(): void
    {
        $user = User::factory()->create();
        $watchStatus = WatchStatus::factory()->create(['user_id' => $user->id, 'episode_id' => Episode::factory()->create()->id]);

        $this->assertTrue($user->watchStatuses->contains($watchStatus));
    }
}
