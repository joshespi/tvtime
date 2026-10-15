<?php

namespace Tests\Feature\Console;

use App\Models\Movie;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesTmdb;
use Tests\TestCase;

class BackfillCastAndTrailerTest extends TestCase
{
    use FakesTmdb;
    use RefreshDatabase;

    public function test_it_fills_cast_trailer_and_imdb_id_for_titles_missing_them(): void
    {
        $show = Show::factory()->create(['tmdb_id' => '1396', 'cast' => null, 'imdb_id' => null]);
        $this->fakeTmdbShow();

        $this->artisan('app:backfill-cast-trailer')
            ->expectsOutputToContain('Backfilled 1 of 1 show(s).')
            ->assertSuccessful();

        $show->refresh();
        $this->assertSame([], $show->cast);
        $this->assertSame('tt0903747', $show->imdb_id);
    }

    public function test_it_skips_titles_that_already_have_cast(): void
    {
        Http::fake();
        Movie::factory()->create(['cast' => []]);

        $this->artisan('app:backfill-cast-trailer')
            ->expectsOutputToContain('Backfilled 0 of 0 movie(s).')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_failed_fetch_is_not_counted_as_backfilled(): void
    {
        Show::factory()->create(['tmdb_id' => '1396', 'cast' => null]);
        Http::fake(['api.themoviedb.org/3/tv/*' => Http::response(null, 500)]);

        $this->artisan('app:backfill-cast-trailer')
            ->expectsOutputToContain('Backfilled 0 of 1 show(s).')
            ->assertSuccessful();
    }
}
