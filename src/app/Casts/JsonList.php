<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Like the built-in 'array' cast, but a NULL column reads as [] — rows synced
 * before a column existed (genres, cast) then render and filter like any
 * other, instead of every reader carrying its own `?? []`. NULL is still what
 * gets stored for null, so whereNull() backfills keep finding those rows.
 *
 * @implements CastsAttributes<array, array|null>
 */
class JsonList implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return $value === null ? [] : (json_decode($value, true) ?? []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : json_encode($value);
    }
}
