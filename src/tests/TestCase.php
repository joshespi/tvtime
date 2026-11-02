<?php

namespace Tests;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Any request a test didn't fake fails loudly instead of reaching TMDB.
        Http::preventStrayRequests();
    }

    /** Tracks $show for $user (a fresh show when none is given) and returns the pivot row. */
    protected function trackShow(User $user, ?Show $show = null, array $attributes = []): UserShow
    {
        return UserShow::factory()->create($attributes + [
            'user_id' => $user->id,
            'show_id' => ($show ?? Show::factory()->create())->id,
        ]);
    }

    /** Puts $movie (a fresh movie when none is given) on $user's list and returns the pivot row. */
    protected function trackMovie(User $user, ?Movie $movie = null, array $attributes = []): UserMovie
    {
        return UserMovie::factory()->create($attributes + [
            'user_id' => $user->id,
            'movie_id' => ($movie ?? Movie::factory()->create())->id,
        ]);
    }

    /** An aired episode (a week ago unless overridden) of a show $user tracks. */
    protected function trackedEpisode(User $user, array $episode = []): Episode
    {
        $episode = Episode::factory()->create($episode + ['air_date' => today()->subWeek()]);
        $this->trackShow($user, $episode->show);

        return $episode;
    }
}
