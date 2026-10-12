<?php

namespace App\Models;

use Database\Factories\WatchStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchStatus extends Model
{
    /** @use HasFactory<WatchStatusFactory> */
    use HasFactory;

    protected $table = 'watch_status';

    protected $fillable = ['user_id', 'episode_id', 'watched_at'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }

    /**
     * Marks the given episodes watched now for a user, creating or refreshing
     * their watch rows in one statement.
     *
     * @param  iterable<int>  $episodeIds
     */
    public static function markWatched(int $userId, iterable $episodeIds): void
    {
        $now = now();
        $rows = [];

        foreach ($episodeIds as $episodeId) {
            $rows[] = ['user_id' => $userId, 'episode_id' => $episodeId, 'watched_at' => $now];
        }

        if ($rows !== []) {
            static::upsert($rows, ['user_id', 'episode_id'], ['watched_at']);
        }
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
