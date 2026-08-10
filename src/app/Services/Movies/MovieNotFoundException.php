<?php

namespace App\Services\Movies;

use RuntimeException;

class MovieNotFoundException extends RuntimeException
{
    public static function forQuery(string $query): self
    {
        return new self("No movie found for \"{$query}\" on TMDB or Trakt.");
    }
}
