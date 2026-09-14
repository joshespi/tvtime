<?php

namespace App\Models;

use App\Models\Concerns\HasTmdbPoster;
use Database\Factories\MovieFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    /** @use HasFactory<MovieFactory> */
    use HasFactory, HasTmdbPoster;

    protected $fillable = [
        'tmdb_id', 'title', 'poster_path', 'overview', 'status', 'vote_average',
        'genres', 'release_date', 'synced_at', 'cast', 'trailer_key',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'release_date' => 'date',
            'vote_average' => 'float',
            'genres' => 'array',
            'cast' => 'array',
        ];
    }

    public function userMovies(): HasMany
    {
        return $this->hasMany(UserMovie::class);
    }
}
