<?php

namespace Tests\Unit\Support;

use App\Models\Show;
use App\Services\Shows\TmdbClient;
use App\Services\Shows\TraktClient;
use App\Support\Provider;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    public function test_from_nullable_returns_null_for_null(): void
    {
        $this->assertNull(Provider::fromNullable(null));
    }

    public function test_from_nullable_resolves_a_valid_value(): void
    {
        $this->assertSame(Provider::Tmdb, Provider::fromNullable('tmdb'));
        $this->assertSame(Provider::Trakt, Provider::fromNullable('trakt'));
    }

    public function test_id_column(): void
    {
        $this->assertSame('tmdb_id', Provider::Tmdb->idColumn());
        $this->assertSame('trakt_id', Provider::Trakt->idColumn());
    }

    public function test_other_returns_the_opposite_provider(): void
    {
        $this->assertSame(Provider::Trakt, Provider::Tmdb->other());
        $this->assertSame(Provider::Tmdb, Provider::Trakt->other());
    }

    public function test_client_returns_the_matching_client(): void
    {
        $tmdb = new TmdbClient;
        $trakt = new TraktClient;

        $this->assertSame($tmdb, Provider::Tmdb->client($tmdb, $trakt));
        $this->assertSame($trakt, Provider::Trakt->client($tmdb, $trakt));
    }

    public function test_label_names_the_provider(): void
    {
        $this->assertSame('TMDB', Provider::Tmdb->label());
        $this->assertSame('Trakt', Provider::Trakt->label());
    }

    public function test_for_record_reads_resolved_source(): void
    {
        $show = new Show;
        $show->resolved_source = 'trakt';

        $this->assertSame(Provider::Trakt, Provider::forRecord($show));
    }

    public function test_for_record_defaults_to_tmdb_when_unresolved(): void
    {
        $this->assertSame(Provider::Tmdb, Provider::forRecord(new Show));
    }

    public function test_external_id_of_reads_the_matching_column(): void
    {
        $record = new Show;
        $record->tmdb_id = '123';
        $record->trakt_id = null;

        $this->assertSame('123', Provider::Tmdb->externalIdOf($record));
        $this->assertNull(Provider::Trakt->externalIdOf($record));
    }
}
