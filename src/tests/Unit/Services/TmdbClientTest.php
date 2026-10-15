<?php

namespace Tests\Unit\Services;

use App\Services\Shows\TmdbClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TmdbClientTest extends TestCase
{
    private TmdbClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new TmdbClient;
    }

    public function test_show_maps_the_tmdb_response(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'poster_path' => '/poster.jpg',
                'number_of_seasons' => 5,
                'overview' => 'A chemistry teacher...',
                'status' => 'Ended',
                'vote_average' => 8.9,
                'genres' => [['name' => 'Drama'], ['name' => 'Crime']],
                'first_air_date' => '2008-01-20',
                'external_ids' => ['imdb_id' => 'tt0903747'],
                'credits' => [
                    'cast' => [
                        ['name' => 'Bryan Cranston', 'character' => 'Walter White', 'profile_path' => '/cranston.jpg'],
                    ],
                ],
                'videos' => [
                    'results' => [
                        ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'abc123'],
                    ],
                ],
            ]),
        ]);

        $show = $this->client->show('1396');

        $this->assertSame([
            'id' => '1396',
            'title' => 'Breaking Bad',
            'poster_path' => '/poster.jpg',
            'number_of_seasons' => 5,
            'overview' => 'A chemistry teacher...',
            'status' => 'Ended',
            'vote_average' => 8.9,
            'genres' => ['Drama', 'Crime'],
            'first_air_date' => '2008-01-20',
            'imdb_id' => 'tt0903747',
            'cast' => [
                // No person id in the credits above, so there's nothing to look
                // an IMDB id up by — the member still maps, with imdb_id null.
                ['name' => 'Bryan Cranston', 'character' => 'Walter White', 'profile_path' => '/cranston.jpg', 'imdb_id' => null],
            ],
            'trailer_key' => 'abc123',
        ], $show);
    }

    public function test_show_imdb_id_is_null_when_external_ids_are_missing_or_empty(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1*' => Http::response(['id' => 1, 'name' => 'A', 'overview' => '', 'first_air_date' => '']),
            'api.themoviedb.org/3/tv/2*' => Http::response(['id' => 2, 'name' => 'B', 'overview' => '', 'first_air_date' => '', 'external_ids' => ['imdb_id' => '']]),
        ]);

        $this->assertNull($this->client->show('1', append: [])['imdb_id']);
        $this->assertNull($this->client->show('2')['imdb_id']);
    }

    public function test_show_returns_null_when_the_request_fails(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/*' => Http::response(null, 404),
        ]);

        $this->assertNull($this->client->show('missing'));
    }

    public function test_show_returns_null_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $this->assertNull($this->client->show('1396'));
    }

    public function test_search_multi_keeps_only_shows_and_movies(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'tv', 'id' => 1, 'name' => 'A Show', 'first_air_date' => '2020-01-01', 'poster_path' => '/a.jpg', 'overview' => 'o1', 'vote_average' => 7.5],
                    ['media_type' => 'movie', 'id' => 2, 'title' => 'A Movie', 'release_date' => '2021-01-01', 'poster_path' => '/b.jpg', 'overview' => 'o2', 'vote_average' => 6.1],
                    ['media_type' => 'person', 'id' => 3, 'name' => 'Someone'],
                ],
            ]),
        ]);

        $results = $this->client->searchMulti('query');

        $this->assertCount(2, $results);
        $this->assertSame('show', $results[0]['type']);
        $this->assertSame('1', $results[0]['id']);
        $this->assertSame('A Show', $results[0]['title']);
        $this->assertSame('2020-01-01', $results[0]['date']);
        $this->assertSame('movie', $results[1]['type']);
        $this->assertSame('A Movie', $results[1]['title']);
        $this->assertSame('2021-01-01', $results[1]['date']);
    }

    public function test_search_multi_returns_null_when_the_request_fails(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response(null, 500),
        ]);

        $this->assertNull($this->client->searchMulti('query'));
    }

    public function test_movie_maps_the_tmdb_response(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'poster_path' => '/matrix.jpg',
                'overview' => 'A hacker...',
                'status' => 'Released',
                'vote_average' => 8.7,
                'genres' => [['name' => 'Action']],
                'release_date' => '1999-03-31',
                'imdb_id' => 'tt0133093',
                'credits' => [
                    'cast' => [
                        ['name' => 'Keanu Reeves', 'character' => 'Neo', 'profile_path' => '/reeves.jpg'],
                    ],
                ],
                'videos' => [
                    'results' => [
                        ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'xyz789'],
                    ],
                ],
            ]),
        ]);

        $movie = $this->client->movie('603');

        $this->assertSame([
            'id' => '603',
            'title' => 'The Matrix',
            'poster_path' => '/matrix.jpg',
            'overview' => 'A hacker...',
            'status' => 'Released',
            'vote_average' => 8.7,
            'genres' => ['Action'],
            'release_date' => '1999-03-31',
            'imdb_id' => 'tt0133093',
            'cast' => [
                ['name' => 'Keanu Reeves', 'character' => 'Neo', 'profile_path' => '/reeves.jpg', 'imdb_id' => null],
            ],
            'trailer_key' => 'xyz789',
        ], $movie);
    }

    public function test_cast_members_with_a_person_id_get_their_imdb_id_resolved(): void
    {
        Http::fake([
            'api.themoviedb.org/3/person/288/external_ids*' => Http::response(['imdb_id' => 'nm0000288']),
            // Not every TMDB person has an IMDB id; an empty one maps to null
            // rather than the empty string.
            'api.themoviedb.org/3/person/999/external_ids*' => Http::response(['imdb_id' => '']),
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'release_date' => '1999-03-31',
                'credits' => [
                    'cast' => [
                        ['id' => 288, 'name' => 'Keanu Reeves', 'character' => 'Neo', 'profile_path' => '/reeves.jpg'],
                        ['id' => 999, 'name' => 'Extra', 'character' => 'Extra', 'profile_path' => null],
                    ],
                ],
            ]),
        ]);

        $cast = $this->client->movie('603')['cast'];

        $this->assertSame('nm0000288', $cast[0]['imdb_id']);
        $this->assertNull($cast[1]['imdb_id']);
    }

    public function test_map_trailer_prefers_official_trailer_over_others(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'overview' => 'A hacker...',
                'release_date' => '1999-03-31',
                'genres' => [],
                'videos' => [
                    'results' => [
                        ['site' => 'YouTube', 'type' => 'Teaser', 'official' => true, 'key' => 'teaser'],
                        ['site' => 'YouTube', 'type' => 'Trailer', 'official' => false, 'key' => 'unofficial-trailer'],
                        ['site' => 'YouTube', 'type' => 'Trailer', 'official' => true, 'key' => 'official-trailer'],
                    ],
                ],
            ]),
        ]);

        $movie = $this->client->movie('603');

        $this->assertSame('official-trailer', $movie['trailer_key']);
    }

    public function test_movie_returns_null_when_the_request_fails(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/*' => Http::response(null, 404),
        ]);

        $this->assertNull($this->client->movie('missing'));
    }

    public function test_episodes_fetches_every_season_concurrently(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/1*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Pilot', 'air_date' => '2008-01-20', 'id' => 111],
                ],
            ]),
            'api.themoviedb.org/3/tv/1396/season/2*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Seven Thirty-Seven', 'air_date' => '2009-03-08', 'id' => 222],
                ],
            ]),
        ]);

        $episodes = $this->client->episodes('1396', 2);

        $this->assertCount(2, $episodes);
        $this->assertSame(['season' => 1, 'episode_number' => 1, 'title' => 'Pilot', 'air_date' => '2008-01-20', 'external_id' => '111'], $episodes[0]);
        $this->assertSame(['season' => 2, 'episode_number' => 1, 'title' => 'Seven Thirty-Seven', 'air_date' => '2009-03-08', 'external_id' => '222'], $episodes[1]);
    }

    public function test_episodes_returns_empty_array_for_a_show_with_no_seasons(): void
    {
        $this->assertSame([], $this->client->episodes('1396', 0));
    }

    public function test_episodes_skips_seasons_that_fail_to_load(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/1*' => Http::response(null, 500),
            'api.themoviedb.org/3/tv/1396/season/2*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Seven Thirty-Seven', 'air_date' => '2009-03-08', 'id' => 222],
                ],
            ]),
        ]);

        $episodes = $this->client->episodes('1396', 2);

        $this->assertCount(1, $episodes);
        $this->assertSame(2, $episodes[0]['season']);
    }

    public function test_episodes_looks_up_season_count_when_not_provided(): void
    {
        // More specific pattern first: Http::fake() matches in order, and the
        // plain tv/1396 pattern would otherwise also swallow the season request.
        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/1*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Pilot', 'air_date' => '2008-01-20', 'id' => 111],
                ],
            ]),
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'overview' => 'A chemistry teacher...',
                'number_of_seasons' => 1,
                'first_air_date' => '2008-01-20',
            ]),
        ]);

        $episodes = $this->client->episodes('1396');

        $this->assertCount(1, $episodes);
    }
}
