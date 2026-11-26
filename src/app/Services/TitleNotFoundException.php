<?php

namespace App\Services;

use RuntimeException;

/** TMDB has no show or movie for the requested id. */
class TitleNotFoundException extends RuntimeException
{
    public static function for(string $type, string $query): self
    {
        return new self("No {$type} found for \"{$query}\" on TMDB.");
    }
}
