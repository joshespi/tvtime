<?php

namespace App\Services\Shows;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TraktClient implements ProviderClient
{
    private string $baseUrl;

    private string $clientId;

    public function __construct()
    {
        $this->baseUrl = config('services.trakt.base_url');
        $this->clientId = (string) config('services.trakt.client_id');
    }

    public function show(string $id): ?array
    {
        $response = $this->request()->get("/shows/{$id}", ['extended' => 'full']);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'id' => (string) $data['ids']['slug'],
            'title' => $data['title'],
            'poster_path' => null,
            'overview' => $data['overview'] ?? null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['rating'] ?? null,
            'genres' => $data['genres'] ?? [],
            'first_air_date' => isset($data['first_aired'])
                ? substr($data['first_aired'], 0, 10)
                : (isset($data['year']) ? "{$data['year']}-01-01" : null),
        ];
    }

    /**
     * Combined show+movie search via Trakt's comma-separated search types.
     *
     * @return array<int, array{type: string, id: string, title: string, poster_path: ?string, date: ?string, overview: ?string, vote_average: ?float, genres: array<int, string>}>
     */
    public function searchMulti(string $query): array
    {
        $response = $this->request()->get('/search/movie,show', ['query' => $query, 'extended' => 'full']);

        if ($response->failed()) {
            Log::warning('Trakt multi-search failed', ['query' => $query, 'status' => $response->status()]);

            return [];
        }

        return collect($response->json())
            ->filter(fn (array $result) => in_array($result['type'] ?? null, ['movie', 'show'], true)
                && isset($result[$result['type']]))
            ->map(function (array $result) {
                $type = $result['type']; // 'movie' or 'show'
                $item = $result[$type];

                return [
                    'type' => $type,
                    'id' => (string) $item['ids']['slug'],
                    'title' => $item['title'],
                    'poster_path' => null, // Trakt doesn't serve images directly
                    'date' => $type === 'movie'
                        ? ($item['released'] ?? (isset($item['year']) ? "{$item['year']}-01-01" : null))
                        : (isset($item['year']) ? "{$item['year']}-01-01" : null),
                    'overview' => $item['overview'] ?? null,
                    'vote_average' => $item['rating'] ?? null,
                    'genres' => $item['genres'] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    public function movie(string $id): ?array
    {
        $response = $this->request()->get("/movies/{$id}", ['extended' => 'full']);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'id' => (string) $data['ids']['slug'],
            'title' => $data['title'],
            'poster_path' => null,
            'overview' => $data['overview'] ?? null,
            'status' => $data['status'] ?? null,
            'vote_average' => $data['rating'] ?? null,
            'genres' => $data['genres'] ?? [],
            'release_date' => $data['released'] ?? (isset($data['year']) ? "{$data['year']}-01-01" : null),
        ];
    }

    /** $numberOfSeasons is unused — Trakt returns every season in one response. */
    public function episodes(string $id, ?int $numberOfSeasons = null): array
    {
        $response = $this->request()->get("/shows/{$id}/seasons", ['extended' => 'episodes']);

        if ($response->failed()) {
            return [];
        }

        $episodes = [];

        foreach ($response->json() as $season) {
            if (($season['number'] ?? 0) < 1) {
                continue; // skip specials (season 0)
            }

            foreach ($season['episodes'] ?? [] as $episode) {
                $episodes[] = [
                    'season' => $season['number'],
                    'episode_number' => $episode['number'],
                    'title' => $episode['title'] ?? null,
                    'air_date' => isset($episode['first_aired'])
                        ? substr($episode['first_aired'], 0, 10)
                        : null,
                    'external_id' => (string) $episode['ids']['trakt'],
                ];
            }
        }

        return $episodes;
    }

    private function request()
    {
        // See TmdbClient::request() — same inline-render timeout reasoning.
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->withHeaders([
                'trakt-api-version' => '2',
                'trakt-api-key' => $this->clientId,
            ]);
    }
}
