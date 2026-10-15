<?php

namespace Tests;

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
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
    protected function trackShow(User $user, ?Show $show = null): UserShow
    {
        return UserShow::factory()->create([
            'user_id' => $user->id,
            'show_id' => ($show ?? Show::factory()->create())->id,
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
