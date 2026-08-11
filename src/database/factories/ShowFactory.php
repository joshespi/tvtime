<?php

namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Show>
 */
class ShowFactory extends Factory
{
    protected $model = Show::class;

    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1, 999999),
            'title' => fake()->unique()->sentence(3),
            'poster_path' => '/poster.jpg',
            'overview' => fake()->paragraph(),
            'status' => 'Returning Series',
            'vote_average' => fake()->randomFloat(1, 0, 10),
            'genres' => ['Drama'],
            'first_air_date' => fake()->date(),
            'synced_at' => now(),
        ];
    }
}
