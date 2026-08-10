<?php

namespace App\Models;

use App\Models\Concerns\HasTmdbPoster;
use Database\Factories\ShowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Show extends Model
{
    /** @use HasFactory<ShowFactory> */
    use HasFactory, HasTmdbPoster;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'first_air_date' => 'date',
            'vote_average' => 'float',
            'genres' => 'array',
        ];
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function userShows(): HasMany
    {
        return $this->hasMany(UserShow::class);
    }

    /**
     * Episodes annotated with `watched` and `hasUnwatchedBefore` (an earlier
     * unwatched episode exists) — the latter drives the "mark through here" prompt.
     */
    public function episodesWithWatchState(int $userId): Collection
    {
        $episodes = $this->episodes()
            ->with(['watchStatuses' => fn ($query) => $query->where('user_id', $userId)])
            ->orderBy('season')
            ->orderBy('episode_number')
            ->get();

        $hasUnwatchedBefore = false;

        foreach ($episodes as $episode) {
            $episode->watched = (bool) $episode->watchStatuses->first()?->watched_at;
            $episode->hasUnwatchedBefore = $hasUnwatchedBefore;

            if (! $episode->watched) {
                $hasUnwatchedBefore = true;
            }
        }

        return $episodes;
    }
}
