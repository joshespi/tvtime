<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\UserMovie;
use App\Policies\UserMoviePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMoviePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id]);

        $this->assertTrue((new UserMoviePolicy)->update($user, $userMovie));
    }

    public function test_non_owner_cannot_update(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse((new UserMoviePolicy)->update($intruder, $userMovie));
    }

    public function test_owner_can_delete(): void
    {
        $user = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $user->id]);

        $this->assertTrue((new UserMoviePolicy)->delete($user, $userMovie));
    }

    public function test_non_owner_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $userMovie = UserMovie::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse((new UserMoviePolicy)->delete($intruder, $userMovie));
    }
}
