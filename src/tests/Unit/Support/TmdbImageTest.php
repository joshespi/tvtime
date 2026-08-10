<?php

namespace Tests\Unit\Support;

use App\Support\TmdbImage;
use Tests\TestCase;

class TmdbImageTest extends TestCase
{
    public function test_returns_null_for_a_missing_poster_path(): void
    {
        $this->assertNull(TmdbImage::url(null));
    }

    public function test_builds_a_url_from_the_configured_base_and_size(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $this->assertSame(
            'https://image.tmdb.org/t/p/w185/poster.jpg',
            TmdbImage::url('/poster.jpg')
        );
    }

    public function test_accepts_a_custom_size(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p']);

        $this->assertSame(
            'https://image.tmdb.org/t/p/w92/poster.jpg',
            TmdbImage::url('/poster.jpg', 'w92')
        );
    }

    public function test_trims_a_trailing_slash_from_the_configured_base(): void
    {
        config(['services.tmdb.image_base_url' => 'https://image.tmdb.org/t/p/']);

        $this->assertSame(
            'https://image.tmdb.org/t/p/w185/poster.jpg',
            TmdbImage::url('/poster.jpg')
        );
    }
}
