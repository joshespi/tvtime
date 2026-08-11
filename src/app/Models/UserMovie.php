<?php

namespace App\Models;

use Database\Factories\UserMovieFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserMovie extends Model
{
    /** @use HasFactory<UserMovieFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /** Every completed viewing, newest first. */
    public function watches(): HasMany
    {
        return $this->hasMany(MovieWatch::class)->orderByDesc('watched_at');
    }

    /** Movies waiting to be watched — including ones re-queued by "Watch again". */
    public function scopeInWatchlist(Builder $query): Builder
    {
        return $query->whereNull('watched_at');
    }

    /**
     * On the "Finished" shelf rather than the watchlist — the row-level twin of
     * scopeInWatchlist(), for splitting an already-loaded collection without a
     * second query. Both exist so no caller has to know that `watched_at` is
     * what carries this, now that watch *history* lives in its own table.
     */
    public function isFinished(): bool
    {
        return $this->watched_at !== null;
    }

    /** Records a completed viewing and moves the movie off the watchlist. */
    public function markWatched(): void
    {
        $watchedAt = now();

        $this->watches()->create(['watched_at' => $watchedAt]);

        $this->update(['watched_at' => $watchedAt]);
    }

    /**
     * Undoes the most recent viewing — for a mis-click, as opposed to
     * requeue(), which keeps the history and just wants to see it again.
     */
    public function markUnwatched(): void
    {
        // watches() is already newest-first, so this is the most recent one.
        $this->watches()->first()?->delete();

        $this->syncWatchedAt();
    }

    /**
     * Realigns the finished flag with the surviving watch history — the movie is
     * only still finished if an earlier viewing remains. Every path that removes
     * a viewing goes through this, so the flag can't drift from the history.
     */
    public function syncWatchedAt(): void
    {
        $this->update(['watched_at' => $this->watches()->first()?->watched_at]);
    }

    /** Puts a finished movie back on the watchlist, keeping its watch history. */
    public function requeue(): void
    {
        $this->update(['watched_at' => null]);
    }
}
