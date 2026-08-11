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

    protected $guarded = ['id'];

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
     * Episodes airing on a given day.
     *
     * A range rather than whereDate(): whereDate() wraps the column in DATE(),
     * which is non-sargable and defeats the air_date index. The range form also
     * works both for MariaDB's `date` column and for the 'Y-m-d 00:00:00'
     * strings the `date` cast writes under SQLite in tests.
     */
    public function scopeAiringOn(Builder $query, CarbonInterface $day): Builder
    {
        return $query->whereBetween('air_date', [
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
        ]);
    }

    /**
     * Episodes that have aired but this user hasn't marked watched yet.
     */
    public function scopeUnwatchedAiredFor(Builder $query, int $userId): Builder
    {
        return $query->where('air_date', '<=', today()->endOfDay())
            ->whereDoesntHave('watchStatuses', function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)->whereNotNull('watched_at');
            });
    }

    /** Episodes that haven't aired yet — for "next episode" countdowns. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('air_date', '>', today()->endOfDay());
    }

    /**
     * Marks this episode and every earlier one in the same show (spanning
     * prior seasons) as watched — for clearing a gap of earlier unwatched episodes.
     */
    public function markWatchedThrough(int $userId): void
    {
        $episodeIds = static::query()
            ->where('show_id', $this->show_id)
            ->where(function (Builder $query) {
                $query->where('season', '<', $this->season)
                    ->orWhere(function (Builder $query) {
                        $query->where('season', $this->season)
                            ->where('episode_number', '<=', $this->episode_number);
                    });
            })
            ->pluck('id');

        $now = now();

        WatchStatus::upsert(
            $episodeIds->map(fn ($episodeId) => [
                'user_id' => $userId,
                'episode_id' => $episodeId,
                'watched_at' => $now,
            ])->all(),
            ['user_id', 'episode_id'],
            ['watched_at'],
        );
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
