<?php

namespace Database\Factories;

use App\Models\MovieWatch;
use App\Models\UserMovie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovieWatch>
 */
class MovieWatchFactory extends Factory
{
    protected $model = MovieWatch::class;

    public function definition(): array
    {
        return [
            'user_movie_id' => UserMovie::factory(),
            'watched_at' => now(),
        ];
    }
}
