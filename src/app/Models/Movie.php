<?php

namespace App\Models;

use App\Casts\JsonList;
use App\Models\Concerns\HasTmdbPoster;
use Carbon\CarbonInterface;
use Database\Factories\MovieFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    /** @use HasFactory<MovieFactory> */
    use HasFactory, HasTmdbPoster;

    protected $fillable = [
        'tmdb_id', 'title', 'poster_path', 'overview', 'status', 'vote_average',
        'genres', 'release_date', 'synced_at', 'cast', 'trailer_key', 'imdb_id',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'release_date' => 'date',
            'vote_average' => 'float',
            'genres' => JsonList::class,
            'cast' => JsonList::class,
        ];
    }

    /** Movies releasing on any day from $from through $to — see Episode::scopeAiringBetween() for why a range. */
    public function scopeReleasingBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('release_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /** The catalog detail page — movies have no page of their own, unlike shows. */
    public function detailUrl(): string
    {
        return route('catalog.show', ['type' => 'movie', 'tmdbId' => $this->tmdb_id]);
    }

    public function userMovies(): HasMany
    {
        return $this->hasMany(UserMovie::class);
    }
}
