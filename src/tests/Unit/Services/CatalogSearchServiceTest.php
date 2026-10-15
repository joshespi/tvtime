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

    public function test_search_returns_tmdb_results(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response([
                'results' => [
                    ['media_type' => 'tv', 'id' => 1, 'name' => 'A Show', 'first_air_date' => '2020-01-01', 'poster_path' => '/a.jpg', 'overview' => 'o', 'vote_average' => 7.0],
                ],
            ]),
        ]);

        $result = $this->service->search('query');

        $this->assertCount(1, $result);
        $this->assertSame('https://image.tmdb.org/t/p/w185/a.jpg', $result[0]['poster_url']);
    }

    public function test_search_returns_empty_when_tmdb_has_no_results(): void
    {
        Http::fake(['api.themoviedb.org/3/search/multi*' => Http::response(['results' => []])]);

        $this->assertSame([], $this->service->search('query'));
    }

    public function test_for_request_skips_the_provider_call_for_a_blank_query(): void
    {
        Http::fake();

        $result = $this->service->forRequest(Request::create('/shows', 'GET', ['q' => '   ']));

        $this->assertSame(['query' => '', 'results' => []], $result);
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
        ]);

        $first = $this->service->search('breaking bad');
        $second = $this->service->search('breaking bad');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_for_request_trims_and_reads_the_query_parameter(): void
    {
        Http::fake(['api.themoviedb.org/3/search/multi*' => Http::response(['results' => []])]);

        $request = Request::create('/shows', 'GET', ['q' => '  query  ']);

        $result = $this->service->forRequest($request);

        $this->assertSame('query', $result['query']);
    }

    public function test_a_failed_search_is_not_cached(): void
    {
        Http::fakeSequence('api.themoviedb.org/3/search/multi*')
            ->push(null, 500)
            ->push(['results' => [['media_type' => 'tv', 'id' => 1, 'name' => 'A Show', 'first_air_date' => '2020-01-01', 'overview' => 'o']]]);

        $this->assertSame([], $this->service->search('blip'));
        $this->assertCount(1, $this->service->search('blip'));
    }
}
