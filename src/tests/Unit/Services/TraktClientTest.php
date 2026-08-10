<?php

namespace Tests\Unit\Services;

use App\Services\Shows\TraktClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TraktClientTest extends TestCase
{
    private TraktClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new TraktClient;
    }

    public function test_show_maps_the_trakt_response(): void
    {
        Http::fake([
            'api.trakt.tv/shows/breaking-bad*' => Http::response([
                'ids' => ['slug' => 'breaking-bad'],
                'title' => 'Breaking Bad',
                'overview' => 'A chemistry teacher...',
                'status' => 'ended',
                'rating' => 9.14,
                'genres' => ['drama', 'crime'],
                'first_aired' => '2008-01-20T02:00:00.000Z',
            ]),
        ]);

        $show = $this->client->show('breaking-bad');

        $this->assertSame([
            'id' => 'breaking-bad',
            'title' => 'Breaking Bad',
            'poster_path' => null,
            'overview' => 'A chemistry teacher...',
            'status' => 'ended',
            'vote_average' => 9.14,
            'genres' => ['drama', 'crime'],
            'first_air_date' => '2008-01-20',
        ], $show);
    }

    public function test_show_falls_back_to_year_when_first_aired_is_missing(): void
    {
        Http::fake([
            'api.trakt.tv/shows/breaking-bad*' => Http::response([
                'ids' => ['slug' => 'breaking-bad'],
                'title' => 'Breaking Bad',
                'year' => 2008,
            ]),
        ]);

        $show = $this->client->show('breaking-bad');

        $this->assertSame('2008-01-01', $show['first_air_date']);
    }

    public function test_show_returns_null_when_the_request_fails(): void
    {
        Http::fake([
            'api.trakt.tv/shows/*' => Http::response(null, 404),
        ]);

        $this->assertNull($this->client->show('missing'));
    }

    public function test_search_multi_keeps_only_movie_and_show_results(): void
    {
        Http::fake([
            'api.trakt.tv/search/movie,show*' => Http::response([
                [
                    'type' => 'show',
                    'show' => ['ids' => ['slug' => 'breaking-bad'], 'title' => 'Breaking Bad', 'year' => 2008, 'overview' => 'o', 'rating' => 9.0, 'genres' => ['drama']],
                ],
                [
                    'type' => 'movie',
                    'movie' => ['ids' => ['slug' => 'the-matrix-1999'], 'title' => 'The Matrix', 'released' => '1999-03-31', 'overview' => 'o2', 'rating' => 8.5, 'genres' => ['action']],
                ],
                [
                    'type' => 'person',
                    'person' => ['ids' => ['slug' => 'someone']],
                ],
            ]),
        ]);

        $results = $this->client->searchMulti('query');

        $this->assertCount(2, $results);
        $this->assertSame('show', $results[0]['type']);
        $this->assertSame('breaking-bad', $results[0]['id']);
        $this->assertSame('2008-01-01', $results[0]['date']);
        $this->assertSame('movie', $results[1]['type']);
        $this->assertSame('the-matrix-1999', $results[1]['id']);
        $this->assertSame('1999-03-31', $results[1]['date']);
    }

    public function test_search_multi_returns_empty_array_when_the_request_fails(): void
    {
        Http::fake([
            'api.trakt.tv/search/movie,show*' => Http::response(null, 500),
        ]);

        $this->assertSame([], $this->client->searchMulti('query'));
    }

    public function test_movie_maps_the_trakt_response(): void
    {
        Http::fake([
            'api.trakt.tv/movies/the-matrix-1999*' => Http::response([
                'ids' => ['slug' => 'the-matrix-1999'],
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'status' => 'released',
                'rating' => 8.7,
                'genres' => ['action'],
                'released' => '1999-03-31',
            ]),
        ]);

        $movie = $this->client->movie('the-matrix-1999');

        $this->assertSame([
            'id' => 'the-matrix-1999',
            'title' => 'The Matrix',
            'poster_path' => null,
            'overview' => 'A hacker...',
            'status' => 'released',
            'vote_average' => 8.7,
            'genres' => ['action'],
            'release_date' => '1999-03-31',
        ], $movie);
    }

    public function test_movie_returns_null_when_the_request_fails(): void
    {
        Http::fake([
            'api.trakt.tv/movies/*' => Http::response(null, 404),
        ]);

        $this->assertNull($this->client->movie('missing'));
    }

    public function test_episodes_flattens_seasons_and_skips_specials(): void
    {
        Http::fake([
            'api.trakt.tv/shows/breaking-bad/seasons*' => Http::response([
                [
                    'number' => 0,
                    'episodes' => [
                        ['number' => 1, 'title' => 'Special', 'first_aired' => '2008-01-01T00:00:00.000Z', 'ids' => ['trakt' => 999]],
                    ],
                ],
                [
                    'number' => 1,
                    'episodes' => [
                        ['number' => 1, 'title' => 'Pilot', 'first_aired' => '2008-01-20T02:00:00.000Z', 'ids' => ['trakt' => 111]],
                    ],
                ],
            ]),
        ]);

        $episodes = $this->client->episodes('breaking-bad');

        $this->assertCount(1, $episodes);
        $this->assertSame([
            'season' => 1,
            'episode_number' => 1,
            'title' => 'Pilot',
            'air_date' => '2008-01-20',
            'external_id' => '111',
        ], $episodes[0]);
    }

    public function test_episodes_returns_empty_array_when_the_request_fails(): void
    {
        Http::fake([
            'api.trakt.tv/shows/*/seasons*' => Http::response(null, 500),
        ]);

        $this->assertSame([], $this->client->episodes('breaking-bad'));
    }
}
