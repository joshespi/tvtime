<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\User;
use App\Models\UserMovie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMovie>
 */
class UserMovieFactory extends Factory
{
    protected $model = UserMovie::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'movie_id' => Movie::factory(),
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
