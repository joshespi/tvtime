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
            'trakt_id' => null,
            'title' => fake()->unique()->sentence(3),
            'poster_path' => '/poster.jpg',
            'overview' => fake()->paragraph(),
            'status' => 'Returning Series',
            'vote_average' => fake()->randomFloat(1, 0, 10),
            'genres' => ['Drama'],
            'first_air_date' => fake()->date(),
            'resolved_source' => 'tmdb',
            'synced_at' => now(),
        ];
    }

    public function fromTrakt(): static
    {
        return $this->state(fn (array $attributes) => [
            'tmdb_id' => null,
            'trakt_id' => fake()->unique()->slug(),
            'resolved_source' => 'trakt',
        ]);
    }
}
