<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\UserShow;
use App\Policies\UserShowPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserShowPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete(): void
    {
        $user = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $user->id]);

        $this->assertTrue((new UserShowPolicy)->delete($user, $userShow));
    }

    public function test_non_owner_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $userShow = UserShow::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse((new UserShowPolicy)->delete($intruder, $userShow));
    }
}
