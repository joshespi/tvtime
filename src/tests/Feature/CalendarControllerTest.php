<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Show;
use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/calendar')->assertRedirect('/login');
    }

    public function test_shows_tracked_episodes_and_movies_for_the_current_month(): void
    {
        $user = User::factory()->create();
        $targetDate = Carbon::now()->startOfMonth()->addDays(4);

        $show = Show::factory()->create();
        UserShow::factory()->create(['user_id' => $user->id, 'show_id' => $show->id]);
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => $targetDate]);

        $movie = Movie::factory()->create(['release_date' => $targetDate]);
        UserMovie::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

        $response = $this->actingAs($user)->get('/calendar');

        $response->assertOk();
        $day = collect($response->viewData('weeks'))
            ->flatten(1)
            ->first(fn (array $day) => $day['date']->isSameDay($targetDate));

        $this->assertSame(1, $day['episodes']->count());
        $this->assertSame(1, $day['movies']->count());
    }

    public function test_ignores_items_the_user_does_not_track(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();
        Episode::factory()->create(['show_id' => $show->id, 'air_date' => Carbon::now()->startOfMonth()->addDays(4)]);

        $response = $this->actingAs($user)->get('/calendar');

        $response->assertOk();
        $episodeCount = collect($response->viewData('weeks'))->flatten(1)->sum(fn ($day) => $day['episodes']->count());
        $this->assertSame(0, $episodeCount);
    }

    public function test_accepts_a_month_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/calendar?month=2030-02');

        $response->assertOk();
        $this->assertSame('2030-02', $response->viewData('month')->format('Y-m'));
    }

    public function test_ignores_an_invalid_month_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/calendar?month=not-a-month');

        $response->assertOk();
        $this->assertSame(Carbon::now()->format('Y-m'), $response->viewData('month')->format('Y-m'));
    }
}
