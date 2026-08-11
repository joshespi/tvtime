<?php

namespace App\Services\Shows;

use App\Support\TmdbImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    private string $baseUrl;

    private string $key;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
        $this->key = (string) config('services.tmdb.key');
    }

    public function show(string $id): ?array
    {
        try {
            $response = $this->request()->get("/tv/{$id}", ['api_key' => $this->key]);
        } catch (ConnectionException $e) {
            Log::warning('TMDB show lookup failed to connect', ['id' => $id, 'message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'id' => (string) $data['id'],
            'title' => $data['name'],
            'poster_path' => $data['poster_path'] ?? null,
            'number_of_seasons' => $data['number_of_seasons'] ?? 0,
            'overview' => $data['overview'] ?: null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['vote_average'] ?? null,
            'genres' => collect($data['genres'] ?? [])->pluck('name')->all(),
            'first_air_date' => $data['first_air_date'] ?: null,
        ];
    }

    /**
     * Combined show+movie search via TMDB's /search/multi. Person results are dropped.
     *
     * @return array<int, array{type: string, id: string, title: string, poster_path: ?string, poster_url: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>
     */
    public function searchMulti(string $query): array
    {
        try {
            $response = $this->request()->get('/search/multi', ['query' => $query, 'api_key' => $this->key]);
        } catch (ConnectionException $e) {
            Log::warning('TMDB multi-search failed to connect', ['query' => $query, 'message' => $e->getMessage()]);

            return [];
        }

        if ($response->failed()) {
            Log::warning('TMDB multi-search failed', ['query' => $query, 'status' => $response->status()]);

            return [];
        }

        return collect($response->json('results', []))
            ->filter(fn (array $result) => in_array($result['media_type'] ?? null, ['tv', 'movie'], true))
            ->map(fn (array $result) => $this->mapResult($result, $result['media_type'] === 'movie' ? 'movie' : 'show'))
            ->values()
            ->all();
    }

    /**
     * Recommendations for several seed titles at once. This runs inline while the
     * dashboard renders, so the seeds go out concurrently rather than one after
     * another. Recommendations are a nice-to-have, so a seed that fails degrades
     * to an empty list instead of failing the page.
     *
     * @param  array<int, array{type: string, id: string}>  $seeds
     * @return array<int, array<int, array{type: string, id: string, title: string, poster_path: ?string, poster_url: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>> Results per seed, in $seeds order.
     */
    public function recommendations(array $seeds): array
    {
        if ($seeds === []) {
            return [];
        }

        $responses = Http::pool(fn ($pool) => collect($seeds)
            ->map(fn (array $seed) => $pool
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout(5)
                ->get($this->recommendationsPath($seed), ['api_key' => $this->key]))
            ->all());

        return collect($seeds)
            ->map(function (array $seed, int $index) use ($responses) {
                $response = $responses[$index] ?? null;

                if (! $response instanceof Response || $response->failed()) {
                    Log::warning('TMDB recommendations failed', ['seed' => $seed]);

                    return [];
                }

                return collect($response->json('results', []))
                    ->map(fn (array $result) => $this->mapResult($result, $seed['type']))
                    ->values()
                    ->all();
            })
            ->all();
    }

    /** @param  array{type: string, id: string}  $seed */
    private function recommendationsPath(array $seed): string
    {
        return $seed['type'] === 'movie'
            ? "/movie/{$seed['id']}/recommendations"
            : "/tv/{$seed['id']}/recommendations";
    }

    /**
     * Shared shape for search/recommendation results, poster URL included so that
     * every consumer of a TMDB result can render one without re-deriving it.
     * Genres are always empty here — these endpoints only carry genre_ids, so
     * names fill in once stored.
     *
     * @return array{type: string, id: string, title: string, poster_path: ?string, poster_url: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}
     */
    private function mapResult(array $result, string $type): array
    {
        $isMovie = $type === 'movie';
        $posterPath = $result['poster_path'] ?? null;

        return [
            'type' => $type,
            'id' => (string) $result['id'],
            'title' => $isMovie ? $result['title'] : $result['name'],
            'poster_path' => $posterPath,
            'poster_url' => TmdbImage::url($posterPath),
            'date' => ($isMovie ? $result['release_date'] : $result['first_air_date']) ?: null,
            'overview' => $result['overview'] ?: null,
            'vote_average' => $result['vote_average'] ?? null,
            'genres' => [],
        ];
    }

    public function movie(string $id): ?array
    {
        try {
            $response = $this->request()->get("/movie/{$id}", ['api_key' => $this->key]);
        } catch (ConnectionException $e) {
            Log::warning('TMDB movie lookup failed to connect', ['id' => $id, 'message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'id' => (string) $data['id'],
            'title' => $data['title'],
            'poster_path' => $data['poster_path'] ?? null,
            'overview' => $data['overview'] ?: null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['vote_average'] ?? null,
            'genres' => collect($data['genres'] ?? [])->pluck('name')->all(),
            'release_date' => $data['release_date'] ?: null,
        ];
    }

    /**
     * @param  ?int  $numberOfSeasons  Pass when already known, to avoid a redundant show() call.
     */
    public function episodes(string $id, ?int $numberOfSeasons = null): array
    {
        if ($numberOfSeasons === null) {
            $show = $this->show($id);

            if (! $show) {
                return [];
            }

            $numberOfSeasons = $show['number_of_seasons'];
        }

        if ($numberOfSeasons < 1) {
            return [];
        }

        // Seasons are independent requests, so fetch them concurrently instead of
        // one at a time — a long-running show can have 15+ seasons, and this runs
        // for every tracked show in the daily refresh job.
        $seasons = range(1, $numberOfSeasons);
        $responses = Http::pool(fn ($pool) => collect($seasons)
            ->map(fn (int $season) => $pool->as($season)
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->get("/tv/{$id}/season/{$season}", ['api_key' => $this->key]))
            ->all());

        $episodes = [];

        foreach ($seasons as $season) {
            $response = $responses[$season] ?? null;

            if (! $response instanceof Response || $response->failed()) {
                continue;
            }

            foreach ($response->json('episodes', []) as $episode) {
                $episodes[] = [
                    'season' => $season,
                    'episode_number' => $episode['episode_number'],
                    'title' => $episode['name'] ?? null,
                    'air_date' => $episode['air_date'] ?: null,
                    'external_id' => (string) $episode['id'],
                ];
            }
        }

        return $episodes;
    }

    private function request()
    {
        // Timeouts matter here: catalog search runs inline during a page render,
        // so an unresponsive provider would otherwise hold a php-fpm worker open
        // for the default 30s.
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5);
    }
}
