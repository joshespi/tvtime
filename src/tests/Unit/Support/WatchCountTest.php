<?php

namespace Tests\Unit\Support;

use App\Support\WatchCount;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WatchCountTest extends TestCase
{
    public function test_phrase_spells_out_one_and_two(): void
    {
        $this->assertSame('once', WatchCount::phrase(1));
        $this->assertSame('twice', WatchCount::phrase(2));
        $this->assertSame('3 times', WatchCount::phrase(3));
    }

    public function test_seen_is_null_when_never_watched(): void
    {
        $this->assertNull(WatchCount::seen(0, null));
    }

    public function test_seen_includes_the_last_date_when_there_is_one(): void
    {
        $this->assertSame('Watched twice · last on May 3, 2025', WatchCount::seen(2, Carbon::parse('2025-05-03')));
    }

    public function test_seen_says_before_for_a_requeued_title_without_a_current_date(): void
    {
        $this->assertSame('Watched once before', WatchCount::seen(1, null));
    }
}
