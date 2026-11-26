<?php

namespace App\Models\Concerns;

use App\Support\TmdbImage;

trait HasTmdbPoster
{
    public function posterUrl(string $size = TmdbImage::SIZE_CARD): ?string
    {
        return TmdbImage::url($this->poster_path, $size);
    }

    /** Small poster for notifications, never null. */
    public function iconUrl(): string
    {
        return $this->posterUrl(TmdbImage::SIZE_ICON) ?? TmdbImage::FALLBACK_ICON;
    }
}
