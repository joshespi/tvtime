<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    protected $fillable = ['show_id', 'season', 'episode_number', 'title', 'air_date', 'tmdb_id'];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
        ];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function watchStatuses(): HasMany
    {
        return $this->hasMany(WatchStatus::class);
    }

    /**
     * Episodes airing on any day from $from through $to.
     *
     * A start/end-of-day range rather than whereDate(): whereDate() wraps the
     * column in DATE(), which is non-sargable and defeats the air_date index.
     * The range form also works both for MariaDB's `date` column and for the
     * 'Y-m-d 00:00:00' strings the `date` cast writes under SQLite in tests.
     */
    public function scopeAiringBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('air_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    public function scopeAiringOn(Builder $query, CarbonInterface $day): Builder
    {
        return $query->airingBetween($day, $day);
    }

    /** Episodes that have already aired — the inverse of scopeUpcoming(). */
    public function scopeAired(Builder $query): Builder
    {
        return $query->where('air_date', '<=', today()->endOfDay());
    }

    /** Episodes this user hasn't marked watched yet, aired or not. */
    public function scopeUnwatchedBy(Builder $query, int $userId): Builder
    {
        return $query->whereDoesntHave('watchStatuses', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)->whereNotNull('watched_at');
        });
    }

    /**
     * Episodes that have aired but this user hasn't marked watched yet.
     */
    public function scopeUnwatchedAiredFor(Builder $query, int $userId): Builder
    {
        return $query->aired()->unwatchedBy($userId);
    }

    /** Episodes that haven't aired yet — for "next episode" countdowns. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('air_date', '>', today()->endOfDay());
    }

    /**
     * The given episode and every earlier one in the same show, spanning prior
     * seasons — for clearing a gap of earlier unwatched episodes.
     */
    public function scopeUpThrough(Builder $query, Episode $episode): Builder
    {
        return $query
            ->where('show_id', $episode->show_id)
            ->where(function (Builder $query) use ($episode) {
                $query->where('season', '<', $episode->season)
                    ->orWhere(function (Builder $query) use ($episode) {
                        $query->where('season', $episode->season)
                            ->where('episode_number', '<=', $episode->episode_number);
                    });
            });
    }

    /** Has a known air date that is today or earlier — the per-row twin of scopeAired(). */
    public function hasAired(): bool
    {
        return $this->air_date !== null && ! $this->isUpcoming();
    }

    /** Has a known air date later than today — the per-row twin of scopeUpcoming(). */
    public function isUpcoming(): bool
    {
        return $this->air_date !== null && $this->air_date->copy()->startOfDay()->gt(today());
    }

    public function code(): string
    {
        return "S{$this->season}E{$this->episode_number}";
    }

    public function label(): string
    {
        return $this->code().($this->title ? " — {$this->title}" : '');
    }
}
