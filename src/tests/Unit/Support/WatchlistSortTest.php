<?php

namespace Tests\Unit\Support;

use App\Support\WatchlistSort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

class WatchlistSortTest extends TestCase
{
    public function test_resolve_defaults_to_added_for_unknown_values(): void
    {
        $this->assertSame('added', WatchlistSort::resolve(null));
        $this->assertSame('added', WatchlistSort::resolve(''));
        $this->assertSame('added', WatchlistSort::resolve('bogus'));
    }

    public function test_resolve_accepts_known_options(): void
    {
        $this->assertSame('added', WatchlistSort::resolve('added'));
        $this->assertSame('release', WatchlistSort::resolve('release'));
        $this->assertSame('title', WatchlistSort::resolve('title'));
    }

    public function test_apply_added_sorts_by_created_at_descending(): void
    {
        $items = $this->items([
            ['title' => 'Older', 'created_at' => Carbon::parse('2024-01-01')],
            ['title' => 'Newer', 'created_at' => Carbon::parse('2024-06-01')],
        ]);

        $sorted = WatchlistSort::apply($items, 'added', fn ($item) => null, fn ($item) => $item->title);

        $this->assertSame(['Newer', 'Older'], $sorted->pluck('title')->all());
    }

    public function test_apply_release_sorts_by_date_descending(): void
    {
        $items = $this->items([
            ['title' => 'Earlier release', 'date' => Carbon::parse('2020-01-01')],
            ['title' => 'Later release', 'date' => Carbon::parse('2030-01-01')],
        ]);

        $sorted = WatchlistSort::apply($items, 'release', fn ($item) => $item->date, fn ($item) => $item->title);

        $this->assertSame(['Later release', 'Earlier release'], $sorted->pluck('title')->all());
    }

    public function test_apply_title_sorts_case_insensitively_ascending(): void
    {
        $items = $this->items([
            ['title' => 'zebra'],
            ['title' => 'Apple'],
            ['title' => 'mango'],
        ]);

        $sorted = WatchlistSort::apply($items, 'title', fn ($item) => null, fn ($item) => $item->title);

        $this->assertSame(['Apple', 'mango', 'zebra'], $sorted->pluck('title')->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function items(array $rows): Collection
    {
        return collect($rows)->map(function (array $row) {
            $item = new stdClass;

            foreach ($row as $key => $value) {
                $item->{$key} = $value;
            }

            return $item;
        });
    }
}
