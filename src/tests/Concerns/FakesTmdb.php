<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;

/** Canned TMDB responses for the one show and one movie the sync tests share. */
trait FakesTmdb
{
    /** Breaking Bad (id 1396) with a one-episode season. */
    protected function fakeTmdbShow(int $seasons = 1): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/1396/season/*' => Http::response([
                'episodes' => [
                    ['episode_number' => 1, 'name' => 'Pilot', 'air_date' => '2008-01-20', 'id' => 111],
                ],
            ]),
            'api.themoviedb.org/3/tv/1396*' => Http::response([
                'id' => 1396,
                'name' => 'Breaking Bad',
                'poster_path' => '/poster.jpg',
                'number_of_seasons' => $seasons,
                'overview' => 'A chemistry teacher...',
                'status' => 'Ended',
                'vote_average' => 8.9,
                'genres' => [['name' => 'Drama']],
                'first_air_date' => '2008-01-20',
                'external_ids' => ['imdb_id' => 'tt0903747'],
            ]),
        ]);
    }

    /** The Matrix (id 603). */
    protected function fakeTmdbMovie(): void
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
            ]),
        ]);
    }

    /** A bare show with no seasons — enough for the daily refresh to succeed. */
    protected function fakeTmdbShowRefresh(string $tmdbId): void
    {
        Http::fake([
            "api.themoviedb.org/3/tv/{$tmdbId}/season/*" => Http::response(['episodes' => []]),
            "api.themoviedb.org/3/tv/{$tmdbId}*" => Http::response([
                'id' => (int) $tmdbId,
                'name' => 'A Show',
                'overview' => 'o',
                'number_of_seasons' => 0,
                'first_air_date' => '2020-01-01',
            ]),
        ]);
    }

    /** A bare movie releasing today — enough for the daily refresh to succeed. */
    protected function fakeTmdbMovieRefresh(string $tmdbId): void
    {
        Http::fake([
            "api.themoviedb.org/3/movie/{$tmdbId}*" => Http::response([
                'id' => (int) $tmdbId,
                'title' => 'A Movie',
                'overview' => 'o',
                'release_date' => today()->toDateString(),
            ]),
        ]);
    }
}
