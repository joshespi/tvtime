<?php

namespace App\Models;

use Database\Factories\MovieWatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A single completed viewing of a movie — see the movie_watches migration. */
class MovieWatch extends Model
{
    /** @use HasFactory<MovieWatchFactory> */
    use HasFactory;

    protected $fillable = ['user_movie_id', 'watched_at'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }

    public function userMovie(): BelongsTo
    {
        return $this->belongsTo(UserMovie::class);
    }
}
