<?php

namespace App\Services\Shows;

use App\Services\TitleNotFoundException;

class ShowNotFoundException extends TitleNotFoundException
{
    public static function forQuery(string $query): self
    {
        return new self("No show found for \"{$query}\" on TMDB.");
    }
}
