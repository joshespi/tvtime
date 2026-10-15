<?php

namespace App\Services\Shows;

use App\Support\TmdbImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    /** How many cast members to keep off a show/movie's credits — TMDB returns the full cast, ordered by billing. */
    private const CAST_LIMIT = 10;

    /**
     * Ceiling on how many seasons episodes() will fan out to in one Http::pool()
     * call. number_of_seasons comes straight from TMDB, so this is a defensive
     * cap rather than a real-world limit — the longest current shows (soaps,
     * long-running late-night) top out well under this.
     */
    private const MAX_SEASONS_PER_FETCH = 75;

    /**
     * TMDB `append_to_response` sections a full show/movie detail fetch pulls in
     * beyond the base payload. Cast/trailer data barely changes once a title's
     * released, so the daily refresh jobs (which already re-fetch show()/movie()
     * for episode/status freshness) pass [] instead — only an initial store()
     * or an on-demand catalog preview needs the full set.
     */
    private const DETAIL_APPEND = ['credits', 'videos'];

    private string $baseUrl;

    private string $key;

    /**
     * IMDB ids already resolved this request/command run, keyed by TMDB person
     * id (null for none or a failed lookup) — the layer in front of the app
     * cache in fetchImdbIds(). Only shared across callers because TmdbClient is
     * bound as a singleton in AppServiceProvider.
     */
    private array $imdbIdCache = [];

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
        $this->key = (string) config('services.tmdb.key');
    }

    /**
     * @param  array<int, string>  $append  Which DETAIL_APPEND sections to request; pass [] to skip them.
     *                                      `external_ids` (the IMDB id) is always added: it costs no follow-up
     *                                      request, and a movie's is already in its base payload.
     * @param  bool  $strict  Throw TmdbUnavailableException on an outage instead of returning null, so "TMDB is down" can be told apart from "no such title".
     */
    public function show(string $id, array $append = self::DETAIL_APPEND, bool $strict = false): ?array
    {
        $data = $this->detail("/tv/{$id}", [...$append, 'external_ids'], 'show', $strict);

        if ($data === null) {
            return null;
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'title' => $data['name'] ?? '',
            'poster_path' => $data['poster_path'] ?? null,
            'number_of_seasons' => $data['number_of_seasons'] ?? 0,
            'overview' => ($data['overview'] ?? null) ?: null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['vote_average'] ?? null,
            'genres' => collect($data['genres'] ?? [])->pluck('name')->all(),
            'first_air_date' => ($data['first_air_date'] ?? null) ?: null,
            'imdb_id' => ($data['external_ids']['imdb_id'] ?? null) ?: null,
            ...$this->mapExtras($data),
        ];
    }

    /**
     * Combined show+movie search via TMDB's /search/multi. Person results are dropped.
     *
     * Null (not an empty list) when TMDB failed, so callers can skip caching it.
     *
     * @return ?array<int, array{type: string, id: string, title: string, poster_path: ?string, poster_url: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>
     */
    public function searchMulti(string $query): ?array
    {
        try {
            $response = $this->request()->get('/search/multi', ['query' => $query]);
        } catch (ConnectionException $e) {
            Log::warning('TMDB multi-search failed to connect', ['query' => $query, 'message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('TMDB multi-search failed', ['query' => $query, 'status' => $response->status()]);

            return null;
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
     * to nothing instead of failing the page — but a failure comes back as null,
     * not an empty list, so RecommendationService can tell "TMDB errored" apart
     * from "TMDB said there's nothing" and skip caching the former. Caching a
     * failure the same as a real empty result would silently blank out that
     * seed's recommendations for the full 12h TTL over one blip.
     *
     * @param  array<int, array{type: string, id: string}>  $seeds
     * @return array<int, ?array<int, array{type: string, id: string, title: string, poster_path: ?string, poster_url: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>> Results per seed, in $seeds order; null where the fetch failed.
     */
    public function recommendations(array $seeds): array
    {
        if ($seeds === []) {
            return [];
        }

        $responses = Http::pool(fn ($pool) => collect($seeds)
            ->map(fn (array $seed) => $this->pooledClient($pool)->get($this->recommendationsPath($seed)))
            ->all());

        return collect($seeds)
            ->map(function (array $seed, int $index) use ($responses) {
                $response = $responses[$index] ?? null;

                if (! $response instanceof Response || $response->failed()) {
                    Log::warning('TMDB recommendations failed', ['seed' => $seed]);

                    return null;
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

    private static function imdbCacheKey(int $personId): string
    {
        return "tmdb:person-imdb:{$personId}";
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
            'title' => $result[$isMovie ? 'title' : 'name'] ?? '',
            'poster_path' => $posterPath,
            'poster_url' => TmdbImage::url($posterPath),
            'date' => ($result[$isMovie ? 'release_date' : 'first_air_date'] ?? null) ?: null,
            'overview' => ($result['overview'] ?? null) ?: null,
            'vote_average' => $result['vote_average'] ?? null,
            'genres' => [],
        ];
    }

    /** @param  array<int, string>  $append  See show()'s docblock — same rationale applies to movies. */
    public function movie(string $id, array $append = self::DETAIL_APPEND, bool $strict = false): ?array
    {
        $data = $this->detail("/movie/{$id}", $append, 'movie', $strict);

        if ($data === null) {
            return null;
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'title' => $data['title'] ?? '',
            'poster_path' => $data['poster_path'] ?? null,
            'overview' => ($data['overview'] ?? null) ?: null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['vote_average'] ?? null,
            'genres' => collect($data['genres'] ?? [])->pluck('name')->all(),
            'release_date' => ($data['release_date'] ?? null) ?: null,
            'imdb_id' => ($data['imdb_id'] ?? null) ?: null,
            ...$this->mapExtras($data),
        ];
    }

    /**
     * The raw payload for one detail endpoint. show() and movie() differ only in
     * the path they hit and the shape they map the result into, so the fetch —
     * timeouts, connection failure, non-2xx — lives here once rather than being
     * restated per title type.
     *
     * @param  array<int, string>  $append
     * @param  string  $type  Only used to label the log line.
     */
    private function detail(string $path, array $append, string $type, bool $strict): ?array
    {
        try {
            $response = $this->request()->get($path, $this->params($append));
        } catch (ConnectionException $e) {
            Log::warning("TMDB {$type} lookup failed to connect", ['path' => $path, 'message' => $e->getMessage()]);

            if ($strict) {
                throw new TmdbUnavailableException('TMDB could not be reached.', previous: $e);
            }

            return null;
        }

        if ($response->failed()) {
            Log::warning("TMDB {$type} lookup failed", ['path' => $path, 'status' => $response->status()]);

            if ($strict && ($response->serverError() || $response->status() === 429)) {
                throw new TmdbUnavailableException("TMDB answered {$response->status()}.");
            }

            return null;
        }

        return $response->json();
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
                'character' => ($member['character'] ?? '') ?: null,
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
     * A credits entry can arrive without a person id; there's nothing to look up
     * for one, but it still occupies its slot in the returned list so the result
     * stays aligned with the cast mapCast() built it from.
     *
     * @param  array<int, ?int>  $personIds
     * @return array<int, ?string> Same order and length as $personIds; null on a missing id or failed lookup.
     */
    private function fetchImdbIds(array $personIds): array
    {
        $toFetch = collect($personIds)
            ->filter(fn (?int $id) => $id !== null)
            ->unique()
            ->reject(fn (int $id) => array_key_exists($id, $this->imdbIdCache))
            ->values();

        // A person's IMDb id never changes, so a found one is also kept in the
        // app cache for good — a recurring actor costs one request ever, not one
        // per title. '' records "TMDB has none" (a hit, not a miss), but only for
        // a month: TMDB fills ids in for newer actors over time. Failed lookups
        // only reach the in-memory cache, so they're retried next run.
        if ($toFetch->isNotEmpty()) {
            $cached = Cache::many($toFetch->map(fn (int $id) => self::imdbCacheKey($id))->all());

            $toFetch = $toFetch->reject(function (int $id) use ($cached) {
                $hit = $cached[self::imdbCacheKey($id)] ?? null;

                if ($hit !== null) {
                    $this->imdbIdCache[$id] = $hit ?: null;
                }

                return $hit !== null;
            })->values();
        }

        if ($toFetch->isNotEmpty()) {
            $responses = Http::pool(fn ($pool) => $toFetch
                ->map(fn (int $personId) => $this->pooledClient($pool)->get("/person/{$personId}/external_ids"))
                ->all());

            $found = [];
            $none = [];

            foreach ($toFetch as $index => $personId) {
                $response = $responses[$index] ?? null;
                $imdbId = $response instanceof Response && $response->successful()
                    ? ($response->json('imdb_id') ?: '')
                    : null;

                $this->imdbIdCache[$personId] = $imdbId ?: null;

                if ($imdbId === '') {
                    $none[self::imdbCacheKey($personId)] = '';
                } elseif ($imdbId !== null) {
                    $found[self::imdbCacheKey($personId)] = $imdbId;
                }
            }

            if ($found !== []) {
                Cache::putMany($found); // no TTL: kept forever
            }
            if ($none !== []) {
                Cache::putMany($none, now()->addMonth());
            }
        }

        return collect($personIds)
            ->map(fn (?int $id) => $id === null ? null : ($this->imdbIdCache[$id] ?? null))
            ->all();
    }

    /** A pool member configured like request(). Pass $key to name the response (Pool::as); omit it for a positionally-indexed one. */
    private function pooledClient(Pool $pool, int|string|null $key = null): PendingRequest
    {
        return $this->configure($key === null ? $pool->newRequest() : $pool->as((string) $key));
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
        // for every tracked show in the daily refresh job. Capped since
        // $numberOfSeasons is TMDB-supplied data, not a value this app controls.
        $seasons = range(1, min($numberOfSeasons, self::MAX_SEASONS_PER_FETCH));
        $responses = Http::pool(fn ($pool) => collect($seasons)
            ->map(fn (int $season) => $this->pooledClient($pool, $season)->get("/tv/{$id}/season/{$season}"))
            ->all());

        $episodes = [];

        foreach ($seasons as $season) {
            $response = $responses[$season] ?? null;

            if (! $response instanceof Response || $response->failed()) {
                Log::warning('TMDB season fetch failed', ['show' => $id, 'season' => $season, 'status' => $response instanceof Response ? $response->status() : null]);

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
     * @return array{append_to_response?: string}
     */
    private function params(array $append): array
    {
        return $append === [] ? [] : ['append_to_response' => implode(',', $append)];
    }

    private function request(): PendingRequest
    {
        return $this->configure(Http::createPendingRequest());
    }

    /**
     * The one configuration every TMDB call gets, standalone or pooled — the
     * api_key rides along as a default query parameter so no call site can
     * forget it. Timeouts matter: catalog search runs inline during a page
     * render, so an unresponsive provider would otherwise hold a php-fpm worker
     * open for the default 30s.
     */
    private function configure(PendingRequest $request): PendingRequest
    {
        return $request
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->withQueryParameters(['api_key' => $this->key]);
    }
}
