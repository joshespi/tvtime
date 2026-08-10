<?php

namespace App\Support;

use App\Services\Shows\ProviderClient;
use App\Services\Shows\TmdbClient;
use App\Services\Shows\TraktClient;
use Illuminate\Database\Eloquent\Model;

/**
 * TMDB vs Trakt as a value instead of repeated 'tmdb'/'trakt' string
 * ternaries — ShowSyncService/MovieSyncService both need "which id column",
 * "which client", and "which id does this record have" for a source.
 */
enum Provider: string
{
    case Tmdb = 'tmdb';
    case Trakt = 'trakt';

    public static function fromNullable(?string $source): ?self
    {
        return $source ? self::from($source) : null;
    }

    public function idColumn(): string
    {
        return "{$this->value}_id";
    }

    public function other(): self
    {
        return $this === self::Tmdb ? self::Trakt : self::Tmdb;
    }

    public function client(TmdbClient $tmdb, TraktClient $trakt): ProviderClient
    {
        return $this === self::Tmdb ? $tmdb : $trakt;
    }

    /** Human-readable name, for UI that offers to switch sources. */
    public function label(): string
    {
        return $this === self::Tmdb ? 'TMDB' : 'Trakt';
    }

    /**
     * The provider a record was resolved from, defaulting to TMDB — the primary
     * source, and the only sensible guess for a record synced before
     * resolved_source was recorded.
     *
     * @param  Model&object{resolved_source: ?string}  $record
     */
    public static function forRecord(Model $record): self
    {
        return self::fromNullable($record->resolved_source) ?? self::Tmdb;
    }

    /**
     * @param  Model&object{tmdb_id: mixed, trakt_id: mixed}  $record
     */
    public function externalIdOf(Model $record): ?string
    {
        $value = $record->{$this->idColumn()};

        return $value !== null ? (string) $value : null;
    }
}
