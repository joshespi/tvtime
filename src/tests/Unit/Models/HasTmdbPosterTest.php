<?php

namespace Tests\Unit\Models;

use App\Models\Movie;
use App\Models\Show;
use Tests\TestCase;

class HasTmdbPosterTest extends TestCase
{
    public function test_poster_url_builds_from_the_configured_base_url(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $show = Show::factory()->make(['poster_path' => '/abc.jpg']);

        $this->assertSame('https://image.tmdb.org/t/p/w185/abc.jpg', $show->posterUrl());
    }

    public function test_poster_url_returns_null_without_a_poster_path(): void
    {
        $movie = Movie::factory()->make(['poster_path' => null]);

        $this->assertNull($movie->posterUrl());
    }

    public function test_poster_url_accepts_a_custom_size(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $movie = Movie::factory()->make(['poster_path' => '/abc.jpg']);

        $this->assertSame('https://image.tmdb.org/t/p/w92/abc.jpg', $movie->posterUrl('w92'));
    }
}
