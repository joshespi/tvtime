<?php

namespace App\Services\Movies;

use App\Services\TitleNotFoundException;

class MovieNotFoundException extends TitleNotFoundException
{
    public static function forQuery(string $query): self
    {
        return new self("No movie found for \"{$query}\" on TMDB.");
    }
}
