<?php

namespace App\Services\Shows;

use RuntimeException;

class ShowNotFoundException extends RuntimeException
{
    public static function forQuery(string $query): self
    {
        return new self("No show found for \"{$query}\" on TMDB.");
    }
}
