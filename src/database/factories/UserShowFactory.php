<?php

namespace Database\Factories;

use App\Models\Show;
use App\Models\User;
use App\Models\UserShow;
use App\Support\TrackingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShow>
 */
class UserShowFactory extends Factory
{
    protected $model = UserShow::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'show_id' => Show::factory(),
            'status' => TrackingStatus::Watching,
        ];
    }

    public function finished(): static
    {
        return $this->state(['status' => TrackingStatus::Finished]);
    }

    public function paused(): static
    {
        return $this->state(['status' => TrackingStatus::Paused]);
    }

    public function dropped(): static
    {
        return $this->state(['status' => TrackingStatus::Dropped]);
    }
}
