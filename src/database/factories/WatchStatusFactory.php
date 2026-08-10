<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\User;
use App\Models\WatchStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchStatus>
 */
class WatchStatusFactory extends Factory
{
    protected $model = WatchStatus::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'episode_id' => Episode::factory(),
            'watched_at' => null,
        ];
    }

    public function watched(): static
    {
        return $this->state(fn (array $attributes) => [
            'watched_at' => now(),
        ]);
    }
}
