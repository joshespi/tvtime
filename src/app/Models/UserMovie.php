<?php

namespace App\Models;

use App\Support\WatchCount;
use Database\Factories\UserMovieFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class UserMovie extends Model
{
    /** @use HasFactory<UserMovieFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'movie_id', 'watched_at', 'rating', 'note'];

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

    /** Every completed viewing. Order at the call site; a default ORDER BY taxes every withCount/whereHas subquery. */
    public function watches(): HasMany
    {
        return $this->hasMany(MovieWatch::class);
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

    /**
     * How many completed viewings this movie has. Prefers the withCount value
     * the list pages already load, but falls back to a query so a caller that
     * forgot withCount('watches') pays a query rather than silently reporting
     * a movie as never seen.
     */
    public function timesWatched(): int
    {
        return (int) ($this->watches_count ?? $this->watches()->count());
    }

    /**
     * How this movie's viewing history reads on a card, e.g. "Watched twice ·
     * last on May 3, 2025" — null when it's never been seen. Lives here so the
     * cards and the search results can't pair the count with a different date.
     */
    public function seenPhrase(): ?string
    {
        return WatchCount::seen($this->timesWatched(), $this->watched_at);
    }

    /** Records a completed viewing and moves the movie off the watchlist. */
    public function markWatched(): void
    {
        $watchedAt = now();

        // Transactional so a failure between the two writes can't leave a
        // MovieWatch row on record while the movie itself still reads as
        // unwatched (or vice versa) — syncWatchedAt() elsewhere trusts that
        // watches() and watched_at never drift apart.
        DB::transaction(function () use ($watchedAt) {
            $this->watches()->create(['watched_at' => $watchedAt]);

            $this->update(['watched_at' => $watchedAt]);
        });
    }

    /**
     * Undoes the most recent viewing — for a mis-click, as opposed to
     * requeue(), which keeps the history and just wants to see it again.
     */
    public function markUnwatched(): void
    {
        DB::transaction(function () {
            // The most recent viewing.
            $this->watches()->latest('watched_at')->first()?->delete();

            $this->syncWatchedAt();
        });
    }

    /**
     * Realigns the finished flag with the surviving watch history — the movie is
     * only still finished if an earlier viewing remains. Every path that removes
     * a viewing goes through this, so the flag can't drift from the history.
     */
    public function syncWatchedAt(): void
    {
        $this->update(['watched_at' => $this->watches()->max('watched_at')]);
    }

    /** Puts a finished movie back on the watchlist, keeping its watch history. */
    public function requeue(): void
    {
        $this->update(['watched_at' => null]);
    }
}
