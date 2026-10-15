<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1, 999999),
            'title' => fake()->unique()->sentence(3),
            'poster_path' => '/poster.jpg',
            'overview' => fake()->paragraph(),
            'status' => 'Released',
            'vote_average' => fake()->randomFloat(1, 0, 10),
            'genres' => ['Action'],
            'release_date' => fake()->date(),
            'imdb_id' => null,
            'synced_at' => now(),
        ];
    }
}
