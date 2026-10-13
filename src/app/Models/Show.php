<?php

namespace App\Models;

use App\Casts\JsonList;
use App\Models\Concerns\HasTmdbPoster;
use Database\Factories\ShowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Show extends Model
{
    /** @use HasFactory<ShowFactory> */
    use HasFactory, HasTmdbPoster;

    protected $fillable = [
        'tmdb_id', 'title', 'poster_path', 'overview', 'status', 'vote_average',
        'genres', 'first_air_date', 'synced_at', 'cast', 'trailer_key',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'first_air_date' => 'date',
            'vote_average' => 'float',
            'genres' => JsonList::class,
            'cast' => JsonList::class,
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
     * The soonest episode that hasn't aired yet — what "Next:" and the countdown
     * badges mean. A relation rather than a constrained eager-load of `episodes`
     * so views can say what they mean, and so it can sit alongside other episode
     * selections in one `with()` without either overwriting the other.
     */
    public function nextUpcomingEpisode(): HasOne
    {
        // id breaks ties: two episodes of a season routinely share an air_date.
        return $this->hasOne(Episode::class)->ofMany(
            ['air_date' => 'min', 'id' => 'min'],
            fn (Builder $query) => $query->upcoming(),
        );
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
