<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use App\Policies\OwnedByUserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OwnedByUserPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{class-string, string}> */
    public static function cases(): array
    {
        return [
            'show update' => [UserShow::class, 'update'],
            'show delete' => [UserShow::class, 'delete'],
            'movie update' => [UserMovie::class, 'update'],
            'movie delete' => [UserMovie::class, 'delete'],
        ];
    }

    #[DataProvider('cases')]
    public function test_owner_is_allowed(string $model, string $ability): void
    {
        $user = User::factory()->create();
        $tracked = $model::factory()->create(['user_id' => $user->id]);

        $this->assertTrue((new OwnedByUserPolicy)->{$ability}($user, $tracked));
    }

    #[DataProvider('cases')]
    public function test_non_owner_is_denied(string $model, string $ability): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $tracked = $model::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse((new OwnedByUserPolicy)->{$ability}($intruder, $tracked));
    }

    public function test_registered_for_both_models(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('update', UserShow::factory()->create(['user_id' => $user->id])));
        $this->assertTrue($user->can('delete', UserMovie::factory()->create(['user_id' => $user->id])));
        $this->assertFalse($user->can('delete', UserShow::factory()->create()));
    }
}
