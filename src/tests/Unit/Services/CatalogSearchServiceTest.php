<?php

namespace Tests\Unit\Services;

use App\Services\Catalog\CatalogSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogSearchServiceTest extends TestCase
{
    private CatalogSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $this->service = app(CatalogSearchService::class);
    }

    public function test_search_uses_tmdb_results_when_available(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'tv', 'id' => 1, 'name' => 'A Show', 'first_air_date' => '2020-01-01', 'poster_path' => '/a.jpg', 'overview' => 'o', 'vote_average' => 7.0],
                ],
            ]),
            'api.trakt.tv/*' => Http::response([]),
        ]);

        $result = $this->service->search('query');

        $this->assertSame('tmdb', $result['source']);
        $this->assertCount(1, $result['results']);
        $this->assertSame('https://image.tmdb.org/t/p/w185/a.jpg', $result['results'][0]['poster_url']);
    }

    public function test_search_falls_back_to_trakt_when_tmdb_has_no_results(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response(['results' => []]),
            'api.trakt.tv/search/movie,show*' => Http::response([
                [
                    'type' => 'show',
                    'show' => ['ids' => ['slug' => 'a-show'], 'title' => 'A Show', 'year' => 2020, 'overview' => 'o', 'rating' => 7.0, 'genres' => []],
                ],
            ]),
        ]);

        $result = $this->service->search('query');

        $this->assertSame('trakt', $result['source']);
        $this->assertCount(1, $result['results']);
        $this->assertNull($result['results'][0]['poster_url']);
    }

    public function test_for_request_skips_the_provider_call_for_a_blank_query(): void
    {
        Http::fake();

        $result = $this->service->forRequest(Request::create('/shows', 'GET', ['q' => '   ']));

        $this->assertSame(['query' => '', 'source' => null, 'results' => []], $result);
        Http::assertNothingSent();
    }

    public function test_repeating_a_search_reuses_the_cached_result(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'tv', 'id' => 1, 'name' => 'A Show', 'first_air_date' => '2020-01-01', 'poster_path' => '/a.jpg', 'overview' => 'o', 'vote_average' => 7.0],
                ],
            ]),
            'api.trakt.tv/*' => Http::response([]),
        ]);

        $first = $this->service->search('breaking bad');
        $second = $this->service->search('breaking bad');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_for_request_trims_and_reads_the_query_parameter(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response(['results' => []]),
            'api.trakt.tv/*' => Http::response([]),
        ]);

        $request = Request::create('/shows', 'GET', ['q' => '  query  ']);

        $result = $this->service->forRequest($request);

        $this->assertSame('query', $result['query']);
    }
}
