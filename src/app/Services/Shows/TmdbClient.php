<?php

namespace App\Services\Shows;

use App\Support\TmdbImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    /** How many cast members to keep off a show/movie's credits — TMDB returns the full cast, ordered by billing. */
    private const CAST_LIMIT = 10;

    /**
     * TMDB `append_to_response` sections a full show/movie detail fetch pulls in
     * beyond the base payload. Cast/trailer data barely changes once a title's
     * released, so the daily refresh jobs (which already re-fetch show()/movie()
     * for episode/status freshness) pass an empty append list instead — only an
     * initial store() or an on-demand catalog preview needs this.
     */
    private const DETAIL_APPEND = ['credits', 'videos'];

    private string $baseUrl;

    private string $key;

    /**
     * IMDB ids already resolved this request/command run, keyed by TMDB person
     * id — avoids re-fetching a recurring actor's id once per title they
     * appear in (e.g. during BackfillCastAndTrailer's library-wide run).
     * Only actually shared across callers because TmdbClient is bound as a
     * singleton in AppServiceProvider — see that binding before assuming this
     * cache spans more than one instance.
     */
    private array $imdbIdCache = [];

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
        $this->key = (string) config('services.tmdb.key');
    }

    /** @param  array<int, string>  $append  Which DETAIL_APPEND-style sections to request; pass [] to skip them. */
    public function show(string $id, array $append = self::DETAIL_APPEND): ?array
    {
        try {
            $response = $this->request()->get("/tv/{$id}", $this->params($append));
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
            ...$this->mapExtras($data),
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
            ->map(fn (array $seed) => $this->pooledClient($pool)
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

    /** @param  array<int, string>  $append  See show()'s docblock — same rationale applies to movies. */
    public function movie(string $id, array $append = self::DETAIL_APPEND): ?array
    {
        try {
            $response = $this->request()->get("/movie/{$id}", $this->params($append));
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
            ...$this->mapExtras($data),
        ];
    }

    /**
     * Cast/trailer off a show/movie's `credits`/`videos` objects — only computed
     * when the corresponding section was actually requested (append_to_response),
     * so a caller that opted out (the daily refresh jobs) isn't doing work on
     * data it doesn't have.
     *
     * @return array{cast: array<int, array{name: string, character: ?string, profile_path: ?string, imdb_id: ?string}>, trailer_key: ?string}
     */
    private function mapExtras(array $data): array
    {
        return [
            'cast' => isset($data['credits']) ? $this->mapCast($data['credits']['cast'] ?? []) : [],
            'trailer_key' => isset($data['videos']) ? $this->mapTrailer($data['videos']['results'] ?? []) : null,
        ];
    }

    /**
     * Top-billed cast off a show/movie's `credits` object, already ordered by
     * TMDB's `order` field — full casts run to 50+ entries, only the leads matter here.
     *
     * @return array<int, array{name: string, character: ?string, profile_path: ?string, imdb_id: ?string}>
     */
    private function mapCast(array $cast): array
    {
        $topBilled = collect($cast)->take(self::CAST_LIMIT)->values();

        $imdbIds = $this->fetchImdbIds($topBilled->pluck('id')->all());

        return $topBilled
            ->map(fn (array $member, int $index) => [
                'name' => $member['name'] ?? 'Unknown',
                'character' => $member['character'] ?: null,
                'profile_path' => $member['profile_path'] ?? null,
                'imdb_id' => $imdbIds[$index] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * IMDB person ids for a batch of TMDB person ids, fetched concurrently (one
     * request per not-yet-cached cast member) via TMDB's `/person/{id}/external_ids`
     * — credits don't carry an IMDB id directly. Only ever reached alongside a
     * DETAIL_APPEND fetch (first track, an on-demand catalog preview, or the
     * cast/trailer backfill command), never the daily refresh jobs.
     *
     * @param  array<int, int>  $personIds
     * @return array<int, ?string> Same order as $personIds; null on a failed lookup.
     */
    private function fetchImdbIds(array $personIds): array
    {
        $toFetch = collect($personIds)->unique()->reject(fn (int $id) => array_key_exists($id, $this->imdbIdCache))->values();

        if ($toFetch->isNotEmpty()) {
            $responses = Http::pool(fn ($pool) => $toFetch
                ->map(fn (int $personId) => $this->pooledClient($pool)->get("/person/{$personId}/external_ids", ['api_key' => $this->key]))
                ->all());

            foreach ($toFetch as $index => $personId) {
                $response = $responses[$index] ?? null;

                $this->imdbIdCache[$personId] = $response instanceof Response && $response->successful()
                    ? ($response->json('imdb_id') ?: null)
                    : null;
            }
        }

        return collect($personIds)->map(fn (int $id) => $this->imdbIdCache[$id] ?? null)->all();
    }

    /** Shared connection config for a pooled request — same timeouts as request(), applied to a pool member instead of a standalone client. */
    private function pooledClient(Pool $pool): PendingRequest
    {
        return $pool->baseUrl($this->baseUrl)->acceptJson()->connectTimeout(2)->timeout(5);
    }

    /**
     * A YouTube video key for a trailer off a show/movie's `videos` object —
     * just the key, not a full URL, since where that key gets turned into a
     * link (youtube.com vs. an embed) is a view concern. Prefers an official
     * trailer, falls back to any trailer, then to any YouTube video at all
     * (a "Teaser" beats no video) — `videos.results` isn't guaranteed to
     * contain a "Trailer"-typed entry at all.
     */
    private function mapTrailer(array $videos): ?string
    {
        $youtube = collect($videos)->filter(fn (array $video) => ($video['site'] ?? null) === 'YouTube');

        $trailers = $youtube->filter(fn (array $video) => ($video['type'] ?? null) === 'Trailer');

        $video = $trailers->firstWhere('official', true) ?? $trailers->first() ?? $youtube->first();

        return $video['key'] ?? null;
    }

    /**
     * @param  ?int  $numberOfSeasons  Pass when already known, to avoid a redundant show() call.
     */
    public function episodes(string $id, ?int $numberOfSeasons = null): array
    {
        if ($numberOfSeasons === null) {
            $show = $this->show($id, append: []);

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

    /**
     * @param  array<int, string>  $append
     * @return array{api_key: string, append_to_response?: string}
     */
    private function params(array $append): array
    {
        $params = ['api_key' => $this->key];

        if ($append !== []) {
            $params['append_to_response'] = implode(',', $append);
        }

        return $params;
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
