<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'show_id' => Show::factory(),
            'season' => 1,
            'episode_number' => fake()->unique()->numberBetween(1, 100000),
            'title' => fake()->sentence(4),
            'air_date' => fake()->date(),
            'tmdb_id' => fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
