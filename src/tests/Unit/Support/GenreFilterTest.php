<?php

namespace Tests\Unit\Support;

use App\Support\GenreFilter;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class GenreFilterTest extends TestCase
{
    private function filter(?string $genre): array
    {
        $items = collect([['genres' => ['Drama', 'Crime']], ['genres' => ['comedy']], ['genres' => ['Drama']]]);

        return GenreFilter::fromRequest(new Request(['genre' => $genre]), $items, fn (array $item) => $item['genres']);
    }

    public function test_options_are_alphabetized_with_an_all_option_first(): void
    {
        [, , $options] = $this->filter(null);

        $this->assertSame(['', 'comedy', 'Crime', 'Drama'], array_keys($options));
        $this->assertSame('All genres', $options['']);
    }

    public function test_a_known_genre_filters_the_items_but_keeps_every_option(): void
    {
        [$items, $genre, $options] = $this->filter('Drama');

        $this->assertSame('Drama', $genre);
        $this->assertCount(2, $items);
        $this->assertCount(4, $options);
    }

    public function test_an_unknown_genre_falls_back_to_unfiltered(): void
    {
        [$items, $genre] = $this->filter('Horror');

        $this->assertNull($genre);
        $this->assertCount(3, $items);
    }
}
