<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\User;
use App\Models\UserMovie;
use Carbon\CarbonInterface;
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

    /**
     * A finished movie. The movie_watches row comes with it — production never
     * sets watched_at without logging the viewing that caused it.
     */
    public function watched(?CarbonInterface $at = null): static
    {
        $at ??= now();

        return $this->state(fn (array $attributes) => ['watched_at' => $at])
            ->afterCreating(fn (UserMovie $userMovie) => $userMovie->watches()->create(['watched_at' => $at]));
    }
}
